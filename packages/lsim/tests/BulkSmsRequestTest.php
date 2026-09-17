<?php

declare(strict_types=1);

namespace Integrify\Lsim\Tests;

use DateTimeImmutable;
use Integrify\Exception\InvalidRequest;
use Integrify\Http\RecordingTransport;
use Integrify\Lsim\BulkSmsClient;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Toplu SMS klientinin **göndərdiyi** sorğu.
 */
final class BulkSmsRequestTest extends TestCase
{
    private RecordingTransport $transport;

    private BulkSmsClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::bulk($this->transport);
    }

    /**
     * @return array<string, mixed>
     */
    private function head(): array
    {
        /** @var array<string, mixed> $body */
        $body = $this->transport->lastRequest()->body;
        /** @var array<string, mixed> $request */
        $request = $body['request'];
        /** @var array<string, mixed> $head */
        $head = $request['head'];

        return $head;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function payloadBody(): array
    {
        /** @var array<string, mixed> $body */
        $body = $this->transport->lastRequest()->body;
        /** @var array<string, mixed> $request */
        $request = $body['request'];
        /** @var list<array<string, mixed>> $rows */
        $rows = $request['body'];

        return $rows;
    }

    public function testEverythingGoesToOneEndpointAsPost(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['taskid' => 1])));

        $this->client->sendSameMessage(1, ['994501234567'], 'Salam');

        $request = $this->transport->lastRequest();

        $this->assertSame('POST', $request->method);
        $this->assertSame('https://www.sendsms.az/smxml/api', $request->uri);
    }

    public function testThePayloadIsWrappedInARequestEnvelope(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['taskid' => 1])));

        $this->client->sendSameMessage(1, ['994501234567'], 'Salam');

        /** @var array<string, mixed> $body */
        $body = $this->transport->lastRequest()->body;
        /** @var array<string, mixed> $request */
        $request = $body['request'];

        $this->assertArrayHasKey('request', $body);
        $this->assertArrayHasKey('head', $request);
        $this->assertArrayHasKey('body', $request);
    }

    public function testOneMessageGoesToEveryNumber(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['taskid' => 1])));

        $this->client->sendSameMessage(7, ['994501234567', '994551234567'], 'Salam');

        $head = $this->head();

        $this->assertSame(7, $head['controlid']);
        $this->assertSame('Salam', $head['bulkmessage']);
        $this->assertSame('submit', $head['operation']);
        $this->assertTrue($head['isbulk']);
        $this->assertSame(
            [['msisdn' => '994501234567'], ['msisdn' => '994551234567']],
            $this->payloadBody(),
        );
    }

    public function testDifferentMessagesArePairedWithTheirNumbers(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['taskid' => 2])));

        $this->client->sendDifferentMessages(
            8,
            ['994501234567', '994551234567'],
            ['Birinci', 'İkinci'],
        );

        $head = $this->head();

        $this->assertFalse($head['isbulk']);
        $this->assertFalse(isset($head['bulkmessage']));
        $this->assertSame(
            [
                ['msisdn' => '994501234567', 'message' => 'Birinci'],
                ['msisdn' => '994551234567', 'message' => 'İkinci'],
            ],
            $this->payloadBody(),
        );
    }

    /**
     * Python `zip()` istifadə edir və artıq elementləri səssizcə atır: bir mesajın
     * heç kimə getməməsi aşkar edilməz xətadır.
     */
    public function testMismatchedListsAreRefusedRatherThanTruncated(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->sendDifferentMessages(1, ['994501234567', '994551234567'], ['Tək mesaj']);
    }

    public function testAnEmptyRecipientListIsRefused(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->sendSameMessage(1, [], 'Salam');
    }

    public function testCredentialsAreAddedToEveryHead(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['units' => 6])));

        $this->client->checkBalance();

        $head = $this->head();

        // Tək SMS API-sindən fərqli olaraq burada parol göndərilir — imza yoxdur.
        $this->assertSame(Factory::LOGIN, $head['login']);
        $this->assertSame(Factory::PASSWORD, $head['password']);
        $this->assertSame('units', $head['operation']);
    }

    public function testReportRequestsCarryNoBody(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['delivered' => 2])));

        $this->client->fetchReport(55);

        /** @var array<string, mixed> $body */
        $body = $this->transport->lastRequest()->body;
        /** @var array<string, mixed> $request */
        $request = $body['request'];

        $this->assertSame(55, $this->head()['taskid']);
        $this->assertSame('report', $this->head()['operation']);
        $this->assertFalse(isset($request['body']));
    }

    public function testEachReportFlavourSendsItsOwnOperation(): void
    {
        $this->transport->queue(
            Response::json(Factory::envelope(0, [])),
            Response::json(Factory::envelope(0, [])),
        );

        $this->client->fetchDetailedReport(55);
        $this->assertSame('detailedreport', $this->head()['operation']);

        $this->client->fetchDetailedReportWithDates(55);
        $this->assertSame('detailedreportwithdate', $this->head()['operation']);
    }

    public function testScheduledSendsFormatTheTimestamp(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['taskid' => 1])));

        $this->client->sendSameMessage(
            1,
            ['994501234567'],
            'Salam',
            new DateTimeImmutable('2026-05-19 15:40:05'),
        );

        $this->assertSame('2026-05-19 15:40:05', $this->head()['scheduled']);
    }

    public function testUnscheduledSendsUseNow(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['taskid' => 1])));

        $this->client->sendSameMessage(1, ['994501234567'], 'Salam');

        $this->assertSame('NOW', $this->head()['scheduled']);
    }

    public function testTheSenderNameCanBeOverriddenPerCall(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['taskid' => 1])));

        $this->client->sendSameMessage(1, ['994501234567'], 'Salam', title: 'Other');

        $this->assertSame('Other', $this->head()['title']);
    }
}
