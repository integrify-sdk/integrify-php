<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Tests;

use Integrify\Http\RecordingTransport;
use Integrify\PostaGuvercini\PostaGuverciniClient;
use Integrify\PostaGuvercini\PostaGuverciniConfig;

/**
 * Testlərin paylaşdığı konfiqurasiya, klientlər və nümunə cavablar.
 *
 * Bütün dəyərlər sabitdir — test dəsti heç bir environment dəyişənindən asılı
 * olmamalıdır.
 */
final class Factory
{
    public const USERNAME = 'test-user';

    public const PASSWORD = 'test-pass';

    public static function config(?string $originator = null): PostaGuverciniConfig
    {
        return new PostaGuverciniConfig(self::USERNAME, self::PASSWORD, $originator);
    }

    public static function client(
        RecordingTransport $transport,
        ?PostaGuverciniConfig $config = null,
    ): PostaGuverciniClient {
        return new PostaGuverciniClient($config ?? self::config(), $transport);
    }

    /**
     * Servisin cavab zərfi.
     *
     * @param array<array-key, mixed>|null $result
     *
     * @return array<string, mixed>
     */
    public static function envelope(int $statusCode = 200, ?array $result = null, string $description = 'Test'): array
    {
        return [
            'StatusCode' => $statusCode,
            'StatusDescription' => $description,
            'Result' => $result,
        ];
    }

    /**
     * Göndərmə cavabının nümunəsi (Python-un `mocks.py` faylından).
     *
     * @return array<string, mixed>
     */
    public static function sent(): array
    {
        return self::envelope(result: [
            ['MessageId' => '1234', 'Receiver' => '994123456789', 'Charge' => 1],
        ]);
    }
}
