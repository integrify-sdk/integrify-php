<?php

declare(strict_types=1);

namespace Integrify\Clopos\Tests;

use Integrify\Clopos\CloposClient;
use Integrify\Clopos\CloposConfig;
use Integrify\Clopos\Dto\Request\NewOrder;
use Integrify\Clopos\Dto\Request\NewOrderCustomer;
use Integrify\Clopos\Dto\Request\NewOrderProduct;
use Integrify\Clopos\Dto\Request\NewOrderService;
use Integrify\Http\RecordingTransport;
use Integrify\Response;

/**
 * Testlərin paylaşdığı konfiqurasiya, klient və nümunə payload-lar.
 */
final class Factory
{
    public const TOKEN = 'testtoken';

    public const VENUE_ID = '3';

    public static function config(
        ?string $clientId = 'cid',
        ?string $clientSecret = 'secret',
        ?string $brand = 'brand',
        ?string $integratorId = '7',
        ?string $venueId = null,
    ): CloposConfig {
        return new CloposConfig(
            clientId: $clientId,
            clientSecret: $clientSecret,
            brand: $brand,
            integratorId: $integratorId,
            venueId: $venueId,
        );
    }

    /**
     * Token-i hazır olan klient — `authenticate()` çağırmadan sorğu atmaq üçün.
     */
    public static function client(
        ?RecordingTransport $transport = null,
        ?CloposConfig $config = null,
        ?string $token = self::TOKEN,
    ): CloposClient {
        return new CloposClient(
            $config ?? self::config(),
            $transport ?? new RecordingTransport(),
            $token,
        );
    }

    /**
     * `authenticate()` cavabı.
     */
    public static function authToken(string $token = 'oauth_abc'): Response
    {
        return Response::json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => 1789731045,
        ]);
    }

    /**
     * Uğurlu siyahı zərfi.
     *
     * @param list<array<string, mixed>> $items
     */
    public static function listResponse(array $items = [], ?int $total = null): Response
    {
        return Response::json([
            'success' => true,
            'message' => 'OK',
            'time' => 12,
            'timestamp' => '2026-09-18T10:30:45+00:00',
            'unix' => 1789727445,
            'total' => $total ?? count($items),
            'sorts' => ['id', 'created_at'],
            'data' => $items,
        ]);
    }

    /**
     * Uğurlu tək obyekt zərfi.
     *
     * @param array<string, mixed> $object
     */
    public static function objectResponse(array $object): Response
    {
        return Response::json([
            'success' => true,
            'time' => 12,
            'timestamp' => '2026-09-18T10:30:45+00:00',
            'unix' => 1789727445,
            'data' => $object,
        ]);
    }

    /**
     * Clopos-un xəta zərfi.
     */
    public static function errorResponse(
        string $message = 'No query results for model [App\\Models\\Auth\\User] 1000',
        int $status = 404,
    ): Response {
        return Response::json([
            'success' => false,
            'error' => [[
                'message' => $message,
                'type' => 'server_side',
                'exception' => 'NotFoundHttpException',
                'http_code' => $status,
            ]],
        ], $status);
    }

    /**
     * Nümunə sifariş — `createOrder()` testləri üçün.
     */
    public static function order(?string $address = null): NewOrder
    {
        return new NewOrder(
            service: new NewOrderService(2, 'Delivery', 1, 'Main'),
            customer: new NewOrderCustomer(9, 'Rashid', address: $address),
            products: [new NewOrderProduct(productId: 1, count: 2)],
        );
    }
}
