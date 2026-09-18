<?php

declare(strict_types=1);

namespace Integrify\Azericard;

use Integrify\Environment;
use Integrify\Exception\MissingConfiguration;
use OpenSSLAsymmetricKey;
use SensitiveParameter;

/**
 * Azericard merchant hesabının məlumatları.
 *
 * Dəyişməz value object-dir və klientə konstruktorda ötürülür — qlobal state və ya
 * sorğu anında environment oxuması yoxdur.
 *
 * > [!IMPORTANT]
 * > **İki ayrı açar var.** MPI (kart ödənişləri) RSA private key istəyir — PKCS#1
 * > və ya PKCS#8 PEM. MT (pul köçürmələri) isə MD5 imzası üçün adi paylaşılan sətir
 * > açarı istəyir. Python kitabxanası ikisini də eyni `AZERICARD_KEY_FILE_PATH`
 * > dəyişənindən oxuyur, halbuki bir fayl hər ikisi ola bilməz — burada onlar
 * > ayrıdır və yalnız istifadə etdiyiniz sistem üçün açar vermək kifayətdir.
 */
final readonly class AzericardConfig
{
    public const DEFAULT_LANGUAGE = 'az';

    /**
     * @param string $merchantId Bankın verdiyi terminal IDsi.
     * @param OpenSSLAsymmetricKey|null $privateKey MPI sorğularını imzalayan RSA açarı.
     *     Yalnız kart əməliyyatları üçün lazımdır.
     * @param string|null $transferKey MT sorğularının MD5 imzası üçün paylaşılan açar.
     *     Yalnız pul köçürmələri üçün lazımdır.
     * @param string|null $merchantName Satıcının adı — kart sahibinin tanıyacağı formada.
     * @param string|null $merchantUrl Satıcının saytı.
     * @param string|null $merchantEmail Satıcının email ünvanı.
     * @param string|null $callbackUrl Nəticənin post olunduğu URL.
     * @param Environment $environment Test və ya prod şlüzü.
     * @param string $language Ödəniş səhifəsinin dili.
     */
    public function __construct(
        public string $merchantId,
        #[SensitiveParameter]
        public ?OpenSSLAsymmetricKey $privateKey = null,
        #[SensitiveParameter]
        public ?string $transferKey = null,
        public ?string $merchantName = null,
        public ?string $merchantUrl = null,
        public ?string $merchantEmail = null,
        public ?string $callbackUrl = null,
        public Environment $environment = Environment::Test,
        public string $language = self::DEFAULT_LANGUAGE,
    ) {
    }

    /**
     * Environment dəyişənlərindən qurur.
     *
     * | Dəyişən | Məcburi |
     * | :--- | :--- |
     * | `AZERICARD_MERCHANT_ID` | bəli |
     * | `AZERICARD_KEY_FILE_PATH` | kart əməliyyatları üçün |
     * | `AZERICARD_TRANSFER_KEY` | pul köçürmələri üçün |
     * | `AZERICARD_MERCHANT_NAME` | kart əməliyyatları üçün |
     * | `AZERICARD_MERCHANT_URL` | kart əməliyyatları üçün |
     * | `AZERICARD_CALLBACK_URL` | kart əməliyyatları üçün |
     * | `AZERICARD_MERCHANT_EMAIL` | xeyr |
     * | `AZERICARD_ENV` | xeyr (`test`) |
     * | `AZERICARD_INTERFACE_LANG` | xeyr (`az`) |
     *
     * Açarlar burada **yoxlanılmır**: yalnız kart əməliyyatları edən tətbiqə MT açarı
     * lazım deyil və əksinə. Açar olmadan həmin sorğunu atmağa cəhd `MissingConfiguration`
     * atır — yəni xəta lazım olduğu anda, lazım olan adla gəlir.
     *
     * @throws MissingConfiguration `AZERICARD_MERCHANT_ID` yoxdursa, `AZERICARD_ENV`
     *     tanınmayan dəyərdirsə, və ya açar faylı oxuna bilmirsə.
     */
    public static function fromEnvironment(): self
    {
        $language = self::read('AZERICARD_INTERFACE_LANG');
        $keyPath = self::read('AZERICARD_KEY_FILE_PATH');

        return new self(
            merchantId: self::require('AZERICARD_MERCHANT_ID'),
            privateKey: $keyPath === '' ? null : self::loadPrivateKey($keyPath),
            transferKey: self::read('AZERICARD_TRANSFER_KEY') ?: null,
            merchantName: self::read('AZERICARD_MERCHANT_NAME') ?: null,
            merchantUrl: self::read('AZERICARD_MERCHANT_URL') ?: null,
            merchantEmail: self::read('AZERICARD_MERCHANT_EMAIL') ?: null,
            callbackUrl: self::read('AZERICARD_CALLBACK_URL') ?: null,
            environment: Environment::parse(self::read('AZERICARD_ENV'), strict: true),
            language: $language === '' ? self::DEFAULT_LANGUAGE : $language,
        );
    }

    /**
     * PEM faylından RSA açarı oxuyur.
     *
     * @throws MissingConfiguration Fayl oxuna bilmirsə, və ya keçərli açar deyilsə.
     */
    public static function loadPrivateKey(string $path): OpenSSLAsymmetricKey
    {
        $pem = @file_get_contents($path);

        if ($pem === false) {
            throw new MissingConfiguration(
                'AZERICARD_KEY_FILE_PATH',
                sprintf('The private key file "%s" could not be read.', $path),
            );
        }

        return self::parsePrivateKey($pem);
    }

    /**
     * PEM mətnindən RSA açarı qurur.
     *
     * Fayl yolu əvəzinə birbaşa mətn qəbul edir, çünki bir çox quraşdırmada sirlər
     * fayl kimi deyil, environment dəyəri kimi verilir — və testlərdə yaddaşdakı
     * açar fayl yaratmaqdan sadədir.
     *
     * @throws MissingConfiguration
     */
    public static function parsePrivateKey(#[SensitiveParameter] string $pem): OpenSSLAsymmetricKey
    {
        $key = openssl_pkey_get_private($pem);

        if ($key === false) {
            throw new MissingConfiguration(
                'AZERICARD_KEY_FILE_PATH',
                sprintf(
                    'The value is not a valid private key (%s). PKCS#1 ("BEGIN RSA PRIVATE KEY") '
                    . 'and PKCS#8 ("BEGIN PRIVATE KEY") are both accepted.',
                    openssl_error_string() ?: 'no OpenSSL error reported',
                ),
            );
        }

        return $key;
    }

    /**
     * MPI sorğuları üçün açar; yoxdursa aydın xəta.
     *
     * @throws MissingConfiguration
     */
    public function privateKey(): OpenSSLAsymmetricKey
    {
        if ($this->privateKey === null) {
            throw new MissingConfiguration(
                'AZERICARD_KEY_FILE_PATH',
                'Card operations are signed with an RSA key. Set the variable, or pass '
                . 'privateKey to the AzericardConfig constructor.',
            );
        }

        return $this->privateKey;
    }

    /**
     * MT sorğuları üçün açar; yoxdursa aydın xəta.
     *
     * @throws MissingConfiguration
     */
    public function transferKey(): string
    {
        if ($this->transferKey === null || $this->transferKey === '') {
            throw new MissingConfiguration(
                'AZERICARD_TRANSFER_KEY',
                'Money transfers are signed with a shared MD5 key, which is NOT the RSA key '
                . 'used for card operations. Set the variable, or pass transferKey to the '
                . 'AzericardConfig constructor.',
            );
        }

        return $this->transferKey;
    }

    /**
     * Sistemə uyğun şlüz ünvanı.
     */
    public function baseUrl(System $system): string
    {
        return $system->baseUrl($this->environment);
    }

    /**
     * Kart əməliyyatlarında məcburi olan dəyər.
     *
     * @throws MissingConfiguration
     */
    public function required(?string $value, string $variable): string
    {
        if ($value === null || $value === '') {
            throw new MissingConfiguration(
                $variable,
                'Card operations require it. Set the variable, or pass the value to the '
                . 'AzericardConfig constructor.',
            );
        }

        return $value;
    }

    /**
     * @throws MissingConfiguration
     */
    private static function require(string $variable): string
    {
        $value = self::read($variable);

        if ($value === '') {
            throw new MissingConfiguration($variable, 'Set it, or pass the value to the AzericardConfig constructor.');
        }

        return $value;
    }

    private static function read(string $variable): string
    {
        $value = getenv($variable);

        if ($value === false || $value === '') {
            /** @var mixed $fallback */
            $fallback = $_ENV[$variable] ?? $_SERVER[$variable] ?? null;
            $value = is_string($fallback) ? $fallback : '';
        }

        return $value;
    }
}
