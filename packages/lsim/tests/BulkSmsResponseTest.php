<?php

declare(strict_types=1);

namespace Integrify\Lsim\Tests;

use Integrify\Exception\ValidationFailed;
use Integrify\Http\RecordingTransport;
use Integrify\Lsim\BulkSmsClient;
use Integrify\Lsim\Enum\BulkCode;
use Integrify\Lsim\Enum\SmsStatus;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Toplu SMS klientinin **aldığı** cavab.
 */
final class BulkSmsResponseTest extends TestCase
{
    private RecordingTransport $transport;

    private BulkSmsClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::bulk($this->transport);
    }

    public function testASubmissionCarriesItsTaskId(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['taskid' => 8921])));

        $submission = $this->client->sendSameMessage(1, ['994501234567'], 'Salam');

        $this->assertTrue($submission->isSuccessful());
        $this->assertSame(BulkCode::Success, $submission->code());
        $this->assertSame(8921, $submission->taskId);
    }

    public function testAFailedSubmissionHasNoTaskId(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(105)));

        $submission = $this->client->sendSameMessage(1, ['994501234567'], 'Salam');

        $this->assertFalse($submission->isSuccessful());
        $this->assertSame(BulkCode::InvalidAuth, $submission->code());
        $this->assertNull($submission->taskId);
    }

    /**
     * LSIM `responsecode`-u bəzən sətir kimi qaytarır; DTO `int` saxlayır.
     */
    public function testANumericStringResponseCodeIsCoerced(): void
    {
        $this->transport->queue(Response::json(Factory::envelope('2', ['taskid' => 3])));

        $submission = $this->client->sendSameMessage(1, ['994501234567'], 'Salam');

        $this->assertSame(2, $submission->responseCode);
        $this->assertSame(BulkCode::Duplicate, $submission->code());
    }

    public function testAnUndocumentedCodeStillHydrates(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(4242)));

        $submission = $this->client->sendSameMessage(1, ['994501234567'], 'Salam');

        $this->assertSame(4242, $submission->responseCode);
        $this->assertNull($submission->code());
    }

    public function testTheSummaryReportCountsEachStatus(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, [
            'delivered' => 2,
            'undelivered' => 1,
            'blackList' => 3,
            'queue' => 4,
        ])));

        $report = $this->client->fetchReport(8921);

        $this->assertTrue($report->isSuccessful());
        $this->assertSame(2, $report->delivered);
        $this->assertSame(1, $report->undelivered);
        $this->assertSame(3, $report->blackList);
        $this->assertSame(4, $report->queue);
    }

    /**
     * Qaytarılmayan sahə `-1` qalır — sıfır deyil, çünki "yoxdur" ilə "sıfırdır"
     * fərqli şeylərdir.
     */
    public function testAbsentCountersStayAtMinusOne(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['delivered' => 2])));

        $report = $this->client->fetchReport(8921);

        $this->assertSame(2, $report->delivered);
        $this->assertSame(-1, $report->expired);
        $this->assertSame(-1, $report->error);
    }

    public function testADetailedReportHydratesEveryRow(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, [
            ['msisdn' => '994501234567', 'message' => 'Test Message 1', 'status' => 2],
            ['msisdn' => '994551234567', 'message' => 'Test Message 2', 'status' => 3],
        ])));

        $report = $this->client->fetchDetailedReport(8921);

        $this->assertTrue($report->isSuccessful());
        $this->assertCount(2, $report->reports);
        $this->assertSame('994501234567', $report->reports[0]->msisdn);
        $this->assertSame(SmsStatus::Delivered, $report->reports[0]->smsStatus());
        $this->assertTrue($report->reports[0]->isDelivered());
        $this->assertSame(SmsStatus::Undelivered, $report->reports[1]->smsStatus());
        $this->assertNull($report->reports[0]->date);
    }

    /**
     * LSIM nömrəni JSON-da ədəd kimi qaytarır. Sətir kimi saxlanılır ki, başındakı
     * sıfırlar itməsin.
     */
    public function testANumericMsisdnBecomesAString(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, [
            ['msisdn' => 994501234567, 'message' => 'Test', 'status' => 2],
        ])));

        $report = $this->client->fetchDetailedReport(8921);

        $this->assertSame('994501234567', $report->reports[0]->msisdn);
    }

    public function testTheDatedReportKeepsItsDates(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, [
            [
                'msisdn' => '994501234567',
                'message' => 'Test',
                'status' => 2,
                'date' => '2026-05-19 15:40:05',
            ],
        ])));

        $report = $this->client->fetchDetailedReportWithDates(8921);

        $this->assertSame('2026-05-19 15:40:05', $report->reports[0]->date);
    }

    public function testADetailedReportWithNoBodyIsAnEmptyList(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(105)));

        $report = $this->client->fetchDetailedReport(0);

        $this->assertFalse($report->isSuccessful());
        $this->assertSame([], $report->reports);
    }

    public function testBalanceReadsTheUnits(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(0, ['units' => 6])));

        $balance = $this->client->checkBalance();

        $this->assertTrue($balance->isSuccessful());
        $this->assertSame(6, $balance->units);
    }

    public function testMissingUnitsFallBackToMinusOne(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(105)));

        $balance = $this->client->checkBalance();

        $this->assertSame(-1, $balance->units);
        $this->assertSame(BulkCode::InvalidAuth, $balance->code());
    }

    /**
     * Gateway JSON əvəzinə HTML qaytarsa, `responsecode` ümumiyyətlə yoxdur.
     * `0` uydurmaq təhlükəlidir — `0` LSIM-də **uğur** deməkdir. Ona görə
     * `ValidationFailed` atılır.
     */
    public function testAMalformedEnvelopeFailsLoudlyInsteadOfLookingSuccessful(): void
    {
        $this->transport->queue(new Response(200, [], '<html>error</html>'));

        $this->expectException(ValidationFailed::class);

        $this->client->checkBalance();
    }
}
