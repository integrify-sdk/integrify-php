<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Tests;

use DateTimeImmutable;
use Integrify\Exception\InvalidRequest;
use Integrify\Http\RecordingTransport;
use Integrify\PostaGuvercini\Dto\Request\SmsMessage;
use Integrify\PostaGuvercini\Enum\Channel;
use Integrify\PostaGuvercini\PostaGuverciniClient;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Klientin **göndərdiyi** sorğu: yol, PascalCase field adları və tarix formatı.
 */
final class PostaGuverciniRequestTest extends TestCase
{
    private RecordingTransport $transport;

    private PostaGuverciniClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::client($this->transport);
    }

    private function ok(): void
    {
        $this->transport->queue(Response::json(Factory::sent()));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $body = $this->transport->lastRequest()->body;
        $this->assertIsArray($body);

        /** @var array<string, mixed> $body */
        return $body;
    }

    public function testSendSmsPostsToTheSingleEndpoint(): void
    {
        $this->ok();

        $this->client->sendSms('Salam', ['994501234567']);

        $request = $this->transport->lastRequest();

        $this->assertSame('POST', $request->method);
        $this->assertSame('https://www.poctgoyercini.com/api_json/v1/Sms/Send_1_N', $request->uri);
        $this->assertSame('application/json', $request->headers['Content-Type']);
    }

    public function testEveryEndpointIsDistinct(): void
    {
        $this->ok();
        $this->client->sendMessages([new SmsMessage('994501234567', 'A')]);
        $this->assertSame(
            'https://www.poctgoyercini.com/api_json/v1/Sms/Send_N_N',
            $this->transport->lastRequest()->uri,
        );

        $this->transport->queue(Response::json(Factory::envelope(result: [])));
        $this->client->getStatus(['1234']);
        $this->assertSame(
            'https://www.poctgoyercini.com/api_json/v1/Sms/Status',
            $this->transport->lastRequest()->uri,
        );

        $this->transport->queue(Response::json(Factory::envelope(result: ['Balance' => 10])));
        $this->client->checkBalance();
        $this->assertSame(
            'https://www.poctgoyercini.com/api_json/v1/Sms/CreditBalance',
            $this->transport->lastRequest()->uri,
        );
    }

    public function testTheFieldNamesArePascalCase(): void
    {
        $this->ok();

        $this->client->sendSms('Salam', ['994501234567']);

        $payload = $this->payload();

        $this->assertSame('Salam', $payload['Message']);
        $this->assertSame(['994501234567'], $payload['Receivers']);
        $this->assertSame('OTP', $payload['Channel']);
        $this->assertSame(Factory::USERNAME, $payload['Username']);
    }

    public function testTheCredentialsTravelInThePayloadNotAHeader(): void
    {
        $this->ok();

        $this->client->sendSms('Salam', ['994501234567']);

        $request = $this->transport->lastRequest();

        // Bu servis parolu imzalamır və hash-ləmir — olduğu kimi body-də göndərir.
        // Test bunu sənədləşdirir ki, body-lərin log-a düşməməsi şüurlu qərar olsun.
        $this->assertSame(Factory::PASSWORD, $this->payload()['Password']);
        $this->assertFalse(isset($request->headers['Authorization']));
    }

    public function testNullFieldsStayInThePayload(): void
    {
        $this->ok();

        $this->client->sendSms('Salam', ['994501234567']);

        $payload = $this->payload();

        // Python `exclude_none` vermir, yəni `null`-lar məftildə gedir.
        $this->assertTrue(array_key_exists('SendDate', $payload));
        $this->assertNull($payload['SendDate']);
        $this->assertTrue(array_key_exists('ExpireDate', $payload));
        $this->assertNull($payload['ExpireDate']);
        $this->assertTrue(array_key_exists('Originator', $payload));
        $this->assertNull($payload['Originator']);
    }

    public function testTheStatusRequestDoesNotCarrySendingFields(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(result: [])));

        $this->client->getStatus(['1234', '1235']);

        $payload = $this->payload();

        $this->assertSame(['1234', '1235'], $payload['MessageIds']);
        // Python sxemi bu field-ləri elan etmir, ona görə burada da olmamalıdır.
        $this->assertFalse(array_key_exists('SendDate', $payload));
        $this->assertFalse(array_key_exists('Channel', $payload));
    }

    public function testTheBalanceRequestCarriesOnlyCredentials(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(result: ['Balance' => 10])));

        $this->client->checkBalance();

        $this->assertSame(['Username', 'Password'], array_keys($this->payload()));
    }

    public function testTheNestedMessageFieldsArePascalCaseToo(): void
    {
        $this->ok();

        $this->client->sendMessages([
            new SmsMessage('994501234567', 'A'),
            new SmsMessage('994501234568', 'B'),
        ]);

        // `to_pascal` Python-da iç-içə TypedDict-ə də tətbiq olunur — burada da
        // `Receiver`/`Message` olmalıdır, `receiver`/`message` yox.
        $this->assertSame([
            ['Receiver' => '994501234567', 'Message' => 'A'],
            ['Receiver' => '994501234568', 'Message' => 'B'],
        ], $this->payload()['Messages']);
    }

    public function testTheChannelDefaultsToOtp(): void
    {
        $this->ok();
        $this->client->sendSms('Salam', ['994501234567']);
        $this->assertSame('OTP', $this->payload()['Channel']);

        $this->ok();
        $this->client->sendSms('Salam', ['994501234567'], channel: Channel::Bulk);
        $this->assertSame('BULK', $this->payload()['Channel']);
    }

    public function testTheOriginatorComesFromTheConfiguration(): void
    {
        $transport = new RecordingTransport();
        $client = Factory::client($transport, Factory::config(originator: 'Shop'));

        $transport->queue(Response::json(Factory::sent()));
        $client->sendSms('Salam', ['994501234567']);

        $body = $transport->lastRequest()->body;
        $this->assertIsArray($body);
        $this->assertSame('Shop', $body['Originator']);
    }

    public function testAnExplicitOriginatorOverridesTheConfiguration(): void
    {
        $transport = new RecordingTransport();
        $client = Factory::client($transport, Factory::config(originator: 'Shop'));

        $transport->queue(Response::json(Factory::sent()));
        $client->sendSms('Salam', ['994501234567'], originator: 'Other');

        $body = $transport->lastRequest()->body;
        $this->assertIsArray($body);
        $this->assertSame('Other', $body['Originator']);
    }

    public function testADateTimeIsFormattedTheWayTheServiceExpects(): void
    {
        $this->ok();

        $this->client->sendSms(
            'Salam',
            ['994501234567'],
            sendDate: new DateTimeImmutable('2026-09-18 10:30:45'),
        );

        // `YYYYMMDD HH:MM` — ayırıcı yoxdur, saniyə də yoxdur.
        $this->assertSame('20260918 10:30', $this->payload()['SendDate']);
    }

    public function testAnAlreadyFormattedStringIsAccepted(): void
    {
        $this->ok();

        $this->client->sendSms('Salam', ['994501234567'], sendDate: '20260918 10:30');

        $this->assertSame('20260918 10:30', $this->payload()['SendDate']);
    }

    public function testAMisformattedDateIsRefusedRatherThanSentImmediately(): void
    {
        // Python `timestamp_to_str()` belə halda `None` qaytarır, yəni planlaşdırılmış
        // SMS səssizcə "indi göndər"ə çevrilir. Burada sorğu ümumiyyətlə getmir.
        $this->expectException(InvalidRequest::class);

        $this->client->sendSms('Salam', ['994501234567'], sendDate: '2026-09-18 10:30');
    }

    public function testAnIsoDateIsRefused(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->sendSms('Salam', ['994501234567'], expireDate: '2026-09-18T10:30:00Z');
    }

    public function testAnEmptyReceiverListIsRefusedBeforeSending(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->sendSms('Salam', []);
    }

    public function testAnEmptyMessageListIsRefusedBeforeSending(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->sendMessages([]);
    }

    public function testAnEmptyMessageIdListIsRefusedBeforeSending(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->getStatus([]);
    }

    public function testAGuardStopsTheRequestReachingTheTransport(): void
    {
        try {
            $this->client->sendSms('Salam', []);
        } catch (InvalidRequest) {
            // gözlənilir
        }

        $this->assertCount(0, $this->transport->requests());
    }

    public function testReceiverListsAreReindexed(): void
    {
        $this->ok();

        // Filtrlənmiş massivin açarları boşluqlu olur; `json_encode` belə massivi
        // siyahı yox, obyekt kimi yazır və servis onu qəbul etmir.
        $receivers = array_filter(['994501234567', '', '994501234568']);

        $this->client->sendSms('Salam', array_values($receivers));

        $this->assertSame(['994501234567', '994501234568'], $this->payload()['Receivers']);
    }
}
