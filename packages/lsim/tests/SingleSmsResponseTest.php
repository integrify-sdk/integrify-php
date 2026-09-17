<?php

declare(strict_types=1);

namespace Integrify\Lsim\Tests;

use Integrify\Http\RecordingTransport;
use Integrify\Lsim\Dto\Response\ReportStatus;
use Integrify\Lsim\Enum\SmsCode;
use Integrify\Lsim\SingleSmsClient;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tək SMS klientinin **aldığı** cavab.
 */
final class SingleSmsResponseTest extends TestCase
{
    private RecordingTransport $transport;

    private SingleSmsClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::single($this->transport);
    }

    public function testASuccessfulSendCarriesTheTransactionId(): void
    {
        $this->transport->queue(Response::json([
            'successMessage' => 'Message sent',
            'obj' => 12345,
            'errorCode' => 108,
        ]));

        $result = $this->client->sendSms('994501234567', 'Salam');

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(12345, $result->obj);
        $this->assertSame(SmsCode::Sent, $result->code());
    }

    public function testANegativeCodeIsAFailure(): void
    {
        $this->transport->queue(Response::json([
            'errorMessage' => 'Wrong number format',
            'errorCode' => -102,
        ]));

        $result = $this->client->sendSms('99455555555', 'Salam');

        $this->assertFalse($result->isSuccessful());
        // `assertSame()` PHPStan üçün narrowing edir, ona görə aşağıda `?->` lazım deyil.
        $this->assertSame(SmsCode::WrongNumberFormat, $result->code());
        $this->assertTrue($result->code()->isFailure());
    }

    /**
     * Cavab DTO-su enum deyil, xam `int` saxlayır — LSIM sabah yeni kod əlavə etsə
     * validasiya sınmamalıdır.
     */
    public function testAnUnknownCodeStillHydrates(): void
    {
        $this->transport->queue(Response::json(['errorCode' => 999]));

        $result = $this->client->sendSms('994501234567', 'Salam');

        $this->assertSame(999, $result->errorCode);
        $this->assertNull($result->code());
    }

    public function testBalanceArrivesInObj(): void
    {
        $this->transport->queue(Response::json(['obj' => 6]));

        $this->assertSame(6, $this->client->checkBalance()->obj);
    }

    public function testCheckStatusReadsABareInteger(): void
    {
        // Bu endpoint JSON qaytarmır — body sadəcə bir ədəddir.
        $this->transport->queue(new Response(200, [], '101'));

        $status = $this->client->checkStatus(42);

        $this->assertSame(101, $status->errorCode);
        $this->assertSame(SmsCode::Delivered, $status->code());
        $this->assertTrue($status->isDelivered());
    }

    public function testCheckStatusSurvivesANonNumericBody(): void
    {
        // Gateway xətası zamanı HTML səhifə gələ bilər — crash olmamalıdır.
        $this->transport->queue(new Response(200, [], '<html>error</html>'));

        $status = $this->client->checkStatus(42);

        $this->assertNull($status->errorCode);
        $this->assertNull($status->code());
        $this->assertFalse($status->isDelivered());
    }

    public function testCheckStatusSurvivesAnEmptyBody(): void
    {
        $this->transport->queue(new Response(200, [], ''));

        $this->assertNull($this->client->checkStatus(42)->errorCode);
    }

    public function testReportStatusParsesANegativeCode(): void
    {
        $status = ReportStatus::fromBody("  -106\n");

        $this->assertSame(-106, $status->errorCode);
        $this->assertSame(SmsCode::InvalidTransactionId, $status->code());
    }

    public function testTheDeliveryReportUsesSnakeCaseOnTheWire(): void
    {
        $this->transport->queue(Response::json([
            'message' => 'OK',
            'delivery_status' => 'DELIVERED',
        ]));

        $report = $this->client->fetchDeliveryReport(42);

        $this->assertSame('OK', $report->message);
        $this->assertSame('DELIVERED', $report->deliveryStatus);
    }

    /**
     * POST göndərilmə endpoint-i `errorCode`-u **ad** kimi qaytarır, rəqəm kimi yox.
     */
    public function testThePostSendResultKeepsTheCodeAsAName(): void
    {
        $this->transport->queue(Response::json([
            'errorMessage' => 'Text is too long',
            'errorCode' => 'INTERNAL_ERROR',
        ]));

        $result = $this->client->scheduleSms('994501234567', str_repeat('test', 251));

        $this->assertSame('INTERNAL_ERROR', $result->errorCode);
        $this->assertFalse($result->isSuccessful());
    }

    public function testAnEmptyResponseBodyStillHydrates(): void
    {
        $this->transport->queue(new Response(200, [], ''));

        $result = $this->client->sendSms('994501234567', 'Salam');

        $this->assertNull($result->obj);
        $this->assertNull($result->errorCode);
    }
}
