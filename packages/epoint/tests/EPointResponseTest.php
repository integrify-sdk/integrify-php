<?php

declare(strict_types=1);

namespace Integrify\EPoint\Tests;

use Integrify\EPoint\Enum\TransactionStatus;
use Integrify\EPoint\Enum\TransactionStatusExtended;
use Integrify\EPoint\EPointClient;
use Integrify\Http\RecordingTransport;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Klientin **qaytardığı** DTO-lar.
 *
 * Mock payload-lar Python kitabxanasının `tests/mocks.py` faylından götürülüb —
 * iki SDK eyni cavabı eyni şəkildə oxumalıdır.
 */
final class EPointResponseTest extends TestCase
{
    private RecordingTransport $transport;

    private EPointClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::client($this->transport);
    }

    public function testPayReturnsTheRedirectUrl(): void
    {
        $this->transport->queue(Response::json([
            'status' => 'success',
            'transaction' => 'texxxxxxxxxx',
            'redirect_url' => 'https://epoint.az/',
        ]));

        $result = $this->client->pay(100, 'AZN', 'order-1');

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(TransactionStatus::Success, $result->status());
        $this->assertSame('texxxxxxxxxx', $result->transaction);
        $this->assertSame('https://epoint.az/', $result->redirectUrl);
    }

    public function testAFailedPaymentStillArrivesAsHttp200(): void
    {
        // EPoint xəta halında da 200 qaytarır, ona görə `RequestFailed` atılmır —
        // nəticəni yalnız `isSuccessful()` verir.
        $this->transport->queue(Response::json(['status' => 'error', 'redirect_url' => null]));

        $result = $this->client->pay(100, 'AZN', 'order-1');

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(TransactionStatus::Error, $result->status());
        $this->assertNull($result->redirectUrl);
    }

    public function testSaveCardReturnsTheCardId(): void
    {
        $this->transport->queue(Response::json([
            'status' => 'success',
            'redirect_url' => 'https://epoint.az',
            'card_id' => 'cexxxxxxxxxx',
        ]));

        $result = $this->client->saveCard();

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('cexxxxxxxxxx', $result->cardId);
    }

    public function testPayWithSavedCardReturnsTheBankFields(): void
    {
        $this->transport->queue(Response::json([
            'status' => 'success',
            'message' => 'Approved',
            'transaction' => 'texxxxxxxxxx',
            'bank_transaction' => 'base64data',
            'bank_response' => '',
            'operation_code' => null,
            'rrn' => 'RRN-123456789',
            'card_mask' => '*******1234',
            'card_name' => 'Name Surname',
            'amount' => 1,
        ]));

        $result = $this->client->payWithSavedCard(1, 'AZN', 'order-1', 'cexxxxxx');

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('RRN-123456789', $result->rrn);
        $this->assertSame('*******1234', $result->cardMask);
        $this->assertSame('Name Surname', $result->cardName);

        // EPoint məbləği JSON ədədi kimi qaytarır; DTO sətir saxlayır ki, qəpik
        // dəqiqliyi `float`-a çevrilməkdən itməsin.
        $this->assertSame('1', $result->amount);
    }

    public function testTheBankCodeKeepsItsRawValueAndGainsAMessage(): void
    {
        $this->transport->queue(Response::json([
            'status' => 'success',
            'code' => '000',
            'amount' => 1,
        ]));

        $result = $this->client->payWithSavedCard(1, 'AZN', 'order-1', 'cexxxxxx');

        // Python kodun özünü mesajla əvəz edir; burada ikisi də var.
        $this->assertSame('000', $result->code);
        $this->assertSame('Təsdiq edildi', $result->codeMessage());
    }

    public function testAnUnknownBankCodeDoesNotBreakTheResponse(): void
    {
        $this->transport->queue(Response::json(['status' => 'success', 'code' => '997']));

        $result = $this->client->payWithSavedCard(1, 'AZN', 'order-1', 'cexxxxxx');

        $this->assertSame('997', $result->code);
        $this->assertNull($result->codeMessage());
    }

    public function testAnUnknownStatusDoesNotBreakValidation(): void
    {
        // DTO xam `string` saxlayır, ona görə EPoint yeni status əlavə etsə
        // validasiya sınmır — `status()` sadəcə `null` qaytarır.
        $this->transport->queue(Response::json(['status' => 'partially_refunded']));

        $result = $this->client->pay(100, 'AZN', 'order-1');

        $this->assertSame('partially_refunded', $result->status);
        $this->assertNull($result->status());
        $this->assertFalse($result->isSuccessful());
    }

    public function testTheStatusRequestTreatsAFailedTransactionAsASuccessfulRequest(): void
    {
        $this->transport->queue(Response::json([
            'status' => 'error',
            'message' => 'Kartda kifayət qədər balans yoxdur',
            'transaction' => 'texxxxxxxxxx',
            'order_id' => 'random_order_id',
        ]));

        $result = $this->client->getTransactionStatus('texxxxxxxxxx');

        // Sorğu alındı: EPoint bizə "tranzaksiya uğursuz olub" dedi.
        $this->assertTrue($result->isSuccessful());
        // Tranzaksiyanın özü uğursuzdur.
        $this->assertFalse($result->isPaid());
        $this->assertSame(TransactionStatusExtended::Error, $result->status());
    }

    public function testTheStatusRequestReportsAServerErrorAsAFailedRequest(): void
    {
        $this->transport->queue(Response::json([
            'status' => 'server_error',
            'message' => 'Signature did not match',
        ]));

        $result = $this->client->getTransactionStatus('texxxxxxxxxx');

        $this->assertFalse($result->isSuccessful());
        $this->assertFalse($result->isPaid());
    }

    public function testTheStatusRequestReportsAPaidTransaction(): void
    {
        $this->transport->queue(Response::json([
            'status' => 'success',
            'message' => 'Təsdiq edildi',
            'transaction' => 'texxxxxxxxxx',
            'rrn' => 'RRN-123456789',
            'card_mask' => '*******1234',
            'card_name' => 'Name Surname',
            'amount' => 1,
            'code' => '',
            'order_id' => 'random_order_id',
            'other_attr' => null,
        ]));

        $result = $this->client->getTransactionStatus('texxxxxxxxxx');

        $this->assertTrue($result->isSuccessful());
        $this->assertTrue($result->isPaid());
        $this->assertFalse($result->isReturned());
        $this->assertSame('random_order_id', $result->orderId);
    }

    public function testTheStatusRequestReportsAReturnedTransaction(): void
    {
        $this->transport->queue(Response::json(['status' => 'returned', 'order_id' => 'o']));

        $result = $this->client->getTransactionStatus('texxxxxxxxxx');

        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->isPaid());
        $this->assertTrue($result->isReturned());
    }

    public function testRefundReturnsTheMinimalShape(): void
    {
        $this->transport->queue(Response::json(['status' => 'success', 'message' => 'Approved']));

        $result = $this->client->refund('texxxxxx', 'AZN');

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('Approved', $result->message);
    }

    public function testSplitPayWithSavedCardReturnsTheSplitAmount(): void
    {
        $this->transport->queue(Response::json([
            'status' => 'success',
            'message' => 'Approved',
            'transaction' => 'texxxxxxxxxx',
            'rrn' => 'RRN-123456789',
            'amount' => 100,
            'split_amount' => 50,
        ]));

        $result = $this->client->splitPayWithSavedCard(100, 'AZN', 'order-1', 'cexxxxxx', 'epoint-user', 50);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('100', $result->amount);
        $this->assertSame('50', $result->splitAmount);
    }

    public function testSplitPayAndSaveCardReturnsTheCardId(): void
    {
        $this->transport->queue(Response::json([
            'status' => 'success',
            'transaction' => 'texxxxxxxxxx',
            'redirect_url' => 'https://epoint.az/',
            'card_id' => 'cexxxxxxxxxx',
        ]));

        $result = $this->client->splitPayAndSaveCard(100, 'AZN', 'order-1', 'epoint-user', 50);

        $this->assertSame('cexxxxxxxxxx', $result->cardId);
        $this->assertSame('https://epoint.az/', $result->redirectUrl);
    }

    public function testABadSignatureIsReportedByEPointAsAServerError(): void
    {
        $this->transport->queue(Response::json([
            'status' => 'server_error',
            'message' => 'Signature did not match',
        ]));

        $result = $this->client->pay(100, 'AZN', 'order-1');

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(TransactionStatus::ServerError, $result->status());
        $this->assertSame('Signature did not match', $result->message);
    }
}
