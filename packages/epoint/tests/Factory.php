<?php

declare(strict_types=1);

namespace Integrify\EPoint\Tests;

use Integrify\EPoint\Callback;
use Integrify\EPoint\EPointClient;
use Integrify\EPoint\EPointConfig;
use Integrify\Http\RecordingTransport;

/**
 * Testlərin paylaşdığı konfiqurasiya, klientlər və zərf köməkçiləri.
 *
 * Bütün dəyərlər sabitdir — test dəsti heç bir environment dəyişənindən asılı
 * olmamalıdır.
 */
final class Factory
{
    public const PUBLIC_KEY = 'i000000001';

    public const PRIVATE_KEY = 'test-private-key';

    public static function config(
        ?string $successRedirectUrl = null,
        ?string $errorRedirectUrl = null,
        string $language = 'az',
    ): EPointConfig {
        return new EPointConfig(
            publicKey: self::PUBLIC_KEY,
            privateKey: self::PRIVATE_KEY,
            language: $language,
            successRedirectUrl: $successRedirectUrl,
            errorRedirectUrl: $errorRedirectUrl,
        );
    }

    public static function client(RecordingTransport $transport, ?EPointConfig $config = null): EPointClient
    {
        return new EPointClient($config ?? self::config(), $transport);
    }

    public static function callback(?EPointConfig $config = null): Callback
    {
        return new Callback($config ?? self::config());
    }

    /**
     * EPoint-in imza düsturu, testdə müstəqil şəkildə yenidən hesablanır.
     *
     * Bu qəsdən `EPointConfig::signature()`-ı çağırmır — əks halda test funksiyanı
     * öz-özü ilə müqayisə edərdi və düsturdaki səhvi tuta bilməzdi.
     */
    public static function signature(string $data): string
    {
        return base64_encode(sha1(self::PRIVATE_KEY . $data . self::PRIVATE_KEY, true));
    }

    /**
     * Göndərilmiş zərfin içindəki payload-u açır.
     *
     * @param array<array-key, mixed>|object|null $body `Request::$body`
     *
     * @return array<string, mixed>
     */
    public static function sent(array|object|null $body): array
    {
        if (!is_array($body) || !is_string($body['data'] ?? null)) {
            return [];
        }

        $json = base64_decode($body['data'], true);

        if ($json === false) {
            return [];
        }

        /** @var array<string, mixed> $payload */
        $payload = (array) json_decode($json, true);

        return $payload;
    }

    /**
     * Callback-in xam body-sini qurur: base64 data + düzgün imza.
     *
     * @param array<string, mixed> $payload
     */
    public static function callbackBody(array $payload): string
    {
        $data = base64_encode((string) json_encode($payload));

        return http_build_query(['data' => $data, 'signature' => self::signature($data)]);
    }
}
