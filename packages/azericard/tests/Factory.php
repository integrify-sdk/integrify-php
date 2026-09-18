<?php

declare(strict_types=1);

namespace Integrify\Azericard\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Integrify\Azericard\AzericardClient;
use Integrify\Azericard\AzericardConfig;
use Integrify\Azericard\Callback;
use Integrify\Environment;
use Integrify\Http\RecordingTransport;
use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * Testlərin paylaşdığı konfiqurasiya, klientlər və açarlar.
 *
 * RSA açarı test zamanı **bir dəfə** generasiya olunur və yaddaşda saxlanılır: fayl
 * yaratmağa ehtiyac yoxdur, və repo-ya heç bir açar düşmür.
 */
final class Factory
{
    public const MERCHANT_ID = 'TERM0001';

    public const TRANSFER_KEY = 'shared-transfer-key';

    private static ?OpenSSLAsymmetricKey $key = null;

    /**
     * Test üçün RSA açarı — prosesdə bir dəfə generasiya olunur.
     */
    public static function privateKey(): OpenSSLAsymmetricKey
    {
        return self::$key ??= self::generateKey();
    }

    /**
     * Açarın generasiyası, iki cəhdlə.
     *
     * `openssl_pkey_new()` açar yaratmaq üçün OpenSSL-in konfiqurasiya faylını
     * oxumağa çalışır. Windows-da PHP `openssl.cnf` ilə gəlmir, ona görə `OPENSSL_CONF`
     * təyin olunmayan maşında funksiya `false` qaytarır və bütün suite tək bir səbəbdən
     * uçur:
     *
     *     error:80000003:system library::No such process
     *
     * Bunun nə açarla, nə də imzalama kodu ilə əlaqəsi var: açarın **oxunması** və
     * `openssl_sign()` konfiqurasiya tələb etmir, yalnız **generasiya** edir. Sistemdəki
     * fayl tapılmayanda paketin öz minimal [`openssl.cnf`](openssl.cnf) faylına keçirik.
     */
    private static function generateKey(): OpenSSLAsymmetricKey
    {
        $arguments = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $config = __DIR__ . '/openssl.cnf';

        $key = openssl_pkey_new($arguments);

        if ($key === false) {
            // OpenSSL xətaları növbəyə yığılır; təmizləməsək, aşağıdakı mesaj birinci
            // cəhdin xətasını göstərərdi, halbuki bizi ikincininki maraqlandırır.
            self::clearOpenSslErrors();

            $key = openssl_pkey_new($arguments + ['config' => $config]);
        }

        if ($key === false) {
            throw new RuntimeException(sprintf(
                'Could not generate a test RSA key, with or without %s: %s.',
                $config,
                openssl_error_string() ?: 'unknown OpenSSL error',
            ));
        }

        return $key;
    }

    /** OpenSSL-in xəta növbəsini boşaldır. */
    private static function clearOpenSslErrors(): void
    {
        while (openssl_error_string() !== false) {
            // Növbə boşalana qədər.
        }
    }

    public static function config(
        Environment $environment = Environment::Test,
        ?OpenSSLAsymmetricKey $privateKey = null,
        ?string $transferKey = self::TRANSFER_KEY,
        ?string $merchantName = 'Test Shop',
        ?string $merchantUrl = 'https://shop.az',
        ?string $callbackUrl = 'https://shop.az/callback',
    ): AzericardConfig {
        return new AzericardConfig(
            merchantId: self::MERCHANT_ID,
            privateKey: $privateKey ?? self::privateKey(),
            transferKey: $transferKey,
            merchantName: $merchantName,
            merchantUrl: $merchantUrl,
            merchantEmail: 'shop@shop.az',
            callbackUrl: $callbackUrl,
            environment: $environment,
        );
    }

    public static function client(
        ?RecordingTransport $transport = null,
        ?AzericardConfig $config = null,
    ): AzericardClient {
        return new AzericardClient($config ?? self::config(), $transport ?? new RecordingTransport());
    }

    public static function callback(?AzericardConfig $config = null): Callback
    {
        return new Callback($config ?? self::config());
    }

    /** Sabit vaxt damğası — testlər saatdan asılı olmamalıdır. */
    public static function timestamp(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-18 10:30:45', new DateTimeZone('UTC'));
    }

    /** Sabit nonce. */
    public static function nonce(): string
    {
        return str_repeat('a', 32);
    }

    /**
     * MT imzası, testdə müstəqil şəkildə yenidən hesablanır.
     *
     * Qəsdən `Signature::md5()`-i çağırmır — əks halda test funksiyanı öz-özü ilə
     * müqayisə edərdi və düsturdaki səhvi tuta bilməzdi.
     *
     * @param list<string> $values
     */
    public static function transferSignature(array $values, string $key = self::TRANSFER_KEY): string
    {
        return md5(implode('', $values) . $key);
    }
}
