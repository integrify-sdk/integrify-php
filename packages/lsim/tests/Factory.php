<?php

declare(strict_types=1);

namespace Integrify\Lsim\Tests;

use Integrify\Http\RecordingTransport;
use Integrify\Lsim\BulkSmsClient;
use Integrify\Lsim\LsimConfig;
use Integrify\Lsim\SingleSmsClient;

/**
 * Testlərin paylaşdığı konfiqurasiya, klientlər və nümunə payload-lar.
 *
 * Bütün dəyərlər sabitdir — test dəsti heç bir environment dəyişənindən asılı
 * olmamalıdır.
 */
final class Factory
{
    public const LOGIN = 'test-login';

    public const PASSWORD = 'test-password';

    public const SENDER = 'TestSender';

    public static function config(): LsimConfig
    {
        return new LsimConfig(self::LOGIN, self::PASSWORD, self::SENDER);
    }

    public static function single(RecordingTransport $transport): SingleSmsClient
    {
        return new SingleSmsClient(self::config(), $transport);
    }

    public static function bulk(RecordingTransport $transport): BulkSmsClient
    {
        return new BulkSmsClient(self::config(), $transport);
    }

    /**
     * LSIM-in imza düsturu, testdə müstəqil şəkildə yenidən hesablanır.
     */
    public static function signature(string $text = '', string $msisdn = '', string $sender = self::SENDER): string
    {
        return md5(md5(self::PASSWORD) . self::LOGIN . $text . $msisdn . $sender);
    }

    public static function balanceSignature(): string
    {
        return md5(md5(self::PASSWORD) . self::LOGIN);
    }

    /**
     * Toplu API-nin cavab zərfi.
     *
     * @param array<string, mixed>|list<mixed>|null $body
     *
     * @return array<string, mixed>
     */
    public static function envelope(int|string $responseCode, array|null $body = null): array
    {
        $response = ['head' => ['responsecode' => $responseCode]];

        if ($body !== null) {
            $response['body'] = $body;
        }

        return ['response' => $response];
    }
}
