<?php

declare(strict_types=1);

namespace Integrify\Lsim\Tests;

use DateTimeImmutable;
use Integrify\Http\RecordingTransport;
use Integrify\Lsim\SingleSmsClient;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tək SMS klientinin **göndərdiyi** sorğu.
 */
final class SingleSmsRequestTest extends TestCase
{
    private RecordingTransport $transport;

    private SingleSmsClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::single($this->transport);
    }

    public function testSendSmsUsesGetWithQueryParameters(): void
    {
        $this->transport->queue(Response::json(['successMessage' => 'ok', 'obj' => 7]));

        $this->client->sendSms('994501234567', 'Salam');

        $request = $this->transport->lastRequest();

        $this->assertSame('GET', $request->method);
        $this->assertSame('https://apps.lsim.az/quicksms/v1/send', $request->uri);
        $this->assertNull($request->body);
        $this->assertSame('994501234567', $request->query['msisdn']);
        $this->assertSame('Salam', $request->query['text']);
        $this->assertSame(Factory::LOGIN, $request->query['login']);
        $this->assertSame(Factory::SENDER, $request->query['sender']);
    }

    public function testThePasswordIsNeverSent(): void
    {
        $this->transport->queue(Response::json(['successMessage' => 'ok']));

        $this->client->sendSms('994501234567', 'Salam');

        $sent = (string) json_encode($this->transport->lastRequest()->query);

        // Parol yalnız imzanın hesablanmasında iştirak edir.
        $this->assertFalse(str_contains($sent, Factory::PASSWORD));
    }

    public function testTheSignatureMatchesLsimsFormula(): void
    {
        $this->transport->queue(Response::json(['successMessage' => 'ok']));

        $this->client->sendSms('994501234567', 'Salam');

        $this->assertSame(
            Factory::signature('Salam', '994501234567'),
            $this->transport->lastRequest()->query['key'],
        );
    }

    public function testTheSignatureFollowsASenderOverride(): void
    {
        $this->transport->queue(Response::json(['successMessage' => 'ok']));

        $this->client->sendSms('994501234567', 'Salam', sender: 'Other');

        $request = $this->transport->lastRequest();

        $this->assertSame('Other', $request->query['sender']);
        $this->assertSame(
            Factory::signature('Salam', '994501234567', 'Other'),
            $request->query['key'],
        );
    }

    public function testUnicodeIsSentAsATextualBooleanInTheQuery(): void
    {
        $this->transport->queue(Response::json(['successMessage' => 'ok']));

        $this->client->sendSms('994501234567', 'Şəkil', unicode: true);

        // `http_build_query()` `true`-nu `1` kimi yazardı; LSIM `true` gözləyir.
        $this->assertSame('true', $this->transport->lastRequest()->query['unicode']);
    }

    public function testUnicodeDefaultsToFalse(): void
    {
        $this->transport->queue(Response::json(['successMessage' => 'ok']));

        $this->client->sendSms('994501234567', 'Salam');

        $this->assertSame('false', $this->transport->lastRequest()->query['unicode']);
    }

    public function testScheduleSmsUsesPostAndAJsonBody(): void
    {
        $this->transport->queue(Response::json(['successMessage' => 'ok']));

        $this->client->scheduleSms('994501234567', 'Salam');

        $request = $this->transport->lastRequest();

        $this->assertSame('POST', $request->method);
        $this->assertSame('https://apps.lsim.az/quicksms/v1/smssender', $request->uri);
        $this->assertIsArray($request->body);
        $this->assertSame([], $request->query);
    }

    public function testAnUnscheduledPostSendsNow(): void
    {
        $this->transport->queue(Response::json(['successMessage' => 'ok']));

        $this->client->scheduleSms('994501234567', 'Salam');

        /** @var array<string, mixed> $body */
        $body = $this->transport->lastRequest()->body;

        $this->assertSame('NOW', $body['scheduled']);
    }

    public function testAScheduledPostFormatsTheTimestamp(): void
    {
        $this->transport->queue(Response::json(['successMessage' => 'ok']));

        $this->client->scheduleSms(
            '994501234567',
            'Salam',
            new DateTimeImmutable('2026-05-19 15:40:05'),
        );

        /** @var array<string, mixed> $body */
        $body = $this->transport->lastRequest()->body;

        $this->assertSame('2026-05-19 15:40:05', $body['scheduled']);
    }

    public function testUnicodeStaysARealBooleanInAJsonBody(): void
    {
        $this->transport->queue(Response::json(['successMessage' => 'ok']));

        $this->client->scheduleSms('994501234567', 'Şəkil', unicode: true);

        /** @var array<string, mixed> $body */
        $body = $this->transport->lastRequest()->body;

        // JSON body-də bool bool qalır — çevirmə yalnız query üçün lazımdır.
        $this->assertTrue($body['unicode']);
    }

    public function testCheckBalanceSignsWithoutTheMessage(): void
    {
        $this->transport->queue(Response::json(['obj' => 6]));

        $this->client->checkBalance();

        $request = $this->transport->lastRequest();

        $this->assertSame('https://apps.lsim.az/quicksms/v1/balance', $request->uri);
        $this->assertSame(Factory::balanceSignature(), $request->query['key']);
        $this->assertFalse(isset($request->query['msisdn']));
    }

    public function testCheckStatusSendsTheTransactionIdAndNoSignature(): void
    {
        $this->transport->queue(new Response(200, [], '101'));

        $this->client->checkStatus(42);

        $request = $this->transport->lastRequest();

        $this->assertSame('https://apps.lsim.az/quicksms/v1/report', $request->uri);
        $this->assertSame(42, $request->query['trans_id']);
        $this->assertFalse(isset($request->query['key']));
    }

    public function testDeliveryReportUsesLsimsOwnFieldName(): void
    {
        $this->transport->queue(Response::json(['message' => 'ok']));

        $this->client->fetchDeliveryReport(42);

        /** @var array<string, mixed> $body */
        $body = $this->transport->lastRequest()->body;

        // LSIM burada `transid` gözləyir, `trans_id` yox.
        $this->assertSame('https://apps.lsim.az/quicksms/v1/smsreporter', $this->transport->lastRequest()->uri);
        $this->assertSame(42, $body['transid']);
    }

    public function testNoContentTypeIsSentOnABodylessGet(): void
    {
        $this->transport->queue(Response::json(['obj' => 6]));

        $this->client->checkBalance();

        $this->assertFalse(isset($this->transport->lastRequest()->headers['Content-Type']));
    }
}
