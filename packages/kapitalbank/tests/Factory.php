<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Tests;

use Integrify\Environment;
use Integrify\Http\RecordingTransport;
use Integrify\Kapitalbank\KapitalClient;
use Integrify\Kapitalbank\KapitalConfig;

/**
 * Testlərin paylaşdığı konfiqurasiya, klientlər və zərf köməkçiləri.
 *
 * Bütün dəyərlər sabitdir — test dəsti heç bir environment dəyişənindən asılı
 * olmamalıdır.
 */
final class Factory
{
    public const USERNAME = 'test-user';

    public const PASSWORD = 'test-pass';

    public static function config(
        Environment $environment = Environment::Test,
        ?string $redirectUrl = null,
        string $language = 'az',
    ): KapitalConfig {
        return new KapitalConfig(
            username: self::USERNAME,
            password: self::PASSWORD,
            environment: $environment,
            language: $language,
            redirectUrl: $redirectUrl,
        );
    }

    public static function client(RecordingTransport $transport, ?KapitalConfig $config = null): KapitalClient
    {
        return new KapitalClient($config ?? self::config(), $transport);
    }

    /**
     * Bankın `{"order": {...}}` zərfi.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function order(array $data): array
    {
        return ['order' => $data];
    }

    /**
     * Bankın `{"tran": {...}}` zərfi.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function tran(array $data): array
    {
        return ['tran' => $data];
    }

    /**
     * Sifariş yaradılması cavabının nümunəsi (Python-un `mocks.py` faylından).
     *
     * @return array<string, mixed>
     */
    public static function createdOrder(): array
    {
        return self::order([
            'id' => 1231,
            'password' => '1231231',
            'hppUrl' => 'https://txpgtst.kapitalbank.az',
        ]);
    }

    /**
     * Sifariş məlumatı cavabının nümunəsi.
     *
     * `OrderInformation`-un field-ləri məcburidir (Python sxemi kimi), ona görə
     * testlər natamam body ilə keçmir — bu da qəsdəndir: bank bu field-ləri həmişə
     * göndərir, göndərmədiyi gün bunu bilmək istəyirik.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function orderInformation(array $overrides = []): array
    {
        return self::order([
            'id' => 1231,
            'typeRid' => 'Order_SMS',
            'status' => 'FullyPaid',
            'lastStatusLogin' => 'merchant',
            'amount' => 10.5,
            'currency' => 'AZN',
            'createTime' => '2026-09-17T10:00:00',
            'type' => ['title' => 'Purchase'],
            ...$overrides,
        ]);
    }

    /**
     * Göndərilmiş zərfin içindəki payload.
     *
     * @param array<array-key, mixed>|object|null $body `Request::$body`
     *
     * @return array<string, mixed>
     */
    public static function sent(array|object|null $body, string $key = 'order'): array
    {
        if (!is_array($body) || !is_array($body[$key] ?? null)) {
            return [];
        }

        /** @var array<string, mixed> $payload */
        $payload = $body[$key];

        return $payload;
    }
}
