<?php

declare(strict_types=1);

namespace Integrify\Azericard\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Integrify\Azericard\AzericardClient;
use Integrify\Azericard\Enum\Action;
use Integrify\Azericard\Enum\AuthorizationType;
use Integrify\Azericard\Enum\TransferStatusCode;
use Integrify\Azericard\Exception\SignatureMismatch;
use Integrify\Http\RecordingTransport;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * HTTP sorğusu **atan** üç metod, və onların cavabları.
 */
final class AzericardRequestTest extends TestCase
{
    private RecordingTransport $transport;

    private AzericardClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::client($this->transport);
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

    public function testGetTransactionStatusPostsToTheMpiHost(): void
    {
        $this->transport->queue(Response::json(['ACTION' => 0]));

        $this->client->getTransactionStatus(
            AuthorizationType::Direct,
            '123456',
            Factory::timestamp(),
            Factory::nonce(),
        );

        $request = $this->transport->lastRequest();

        $this->assertSame('POST', $request->method);
        $this->assertSame('https://testmpi.3dsecure.az/cgi-bin/cgi_link', $request->uri);

        $payload = $this->payload();
        $this->assertSame('90', $payload['TRTYPE']);
        $this->assertSame('1', $payload['TRAN_TRTYPE']);
        $this->assertSame('123456', $payload['ORDER']);
    }

    public function testTheTransferEndpointsPostToTheMtHost(): void
    {
        $this->transport->queue(Response::json(['ResponseCode' => '0']));
        $this->client->confirmTransfer('Shop', 'SRN001', 10.5, '944', Factory::timestamp());
        $this->assertSame('https://testmt.azericard.com/api/confirm', $this->transport->lastRequest()->uri);

        $this->transport->queue(Response::json(['ResponseCode' => '0']));
        $this->client->declineTransfer('Shop', 'SRN001', 10.5, '944', Factory::timestamp());
        $this->assertSame('https://testmt.azericard.com/api/decline', $this->transport->lastRequest()->uri);
    }

    public function testTheTransferPayloadIsPascalCaseAndSigned(): void
    {
        $this->transport->queue(Response::json(['ResponseCode' => '0']));

        $this->client->confirmTransfer('Shop', 'SRN001', 10.5, '944', Factory::timestamp());

        $payload = $this->payload();

        $this->assertSame(
            ['Merchant', 'SRN', 'Amount', 'Cur', 'Timestamp', 'Signature'],
            array_keys($payload),
        );
        $this->assertSame(
            Factory::transferSignature(['Shop', 'SRN001', '10.5', '944', '20260918103045']),
            $payload['Signature'],
        );
    }

    public function testTheTimestampIsUtcNotLocalTime(): void
    {
        $this->transport->queue(Response::json(['ResponseCode' => '0']));

        // Bakı vaxtı ilə 14:30 UTC-də 10:30-dur. Python `datetime.now()` — yəni
        // serverin yerli vaxtını — göndərir, halbuki Azericard field-i GMT olaraq
        // təyin edir; şlüz vaxt fərqinə görə sorğunu rədd edə bilər.
        $baku = new DateTimeImmutable('2026-09-18 14:30:45', new DateTimeZone('Asia/Baku'));

        $this->client->confirmTransfer('Shop', 'SRN001', 10.5, '944', $baku);

        $this->assertSame('20260918103045', $this->payload()['Timestamp']);
    }

    public function testTheStatusResponseUsesTheServicesSpacedFieldNames(): void
    {
        $this->transport->queue(Response::json([
            'ACTION' => 0,
            'Response code' => '00',
            'Transaction Status message' => 'Approved',
            'TERMINAL' => Factory::MERCHANT_ID,
            'Card number' => '4169********1234',
            'Transaction amount' => 10.5,
            'Transaction currency' => '944',
            'Transaction date' => '20260918103045',
            'Merchant order id' => '123456',
            'Banks approval code' => '123456',
            'Transaction RRN' => '111111111111',
            'INT_REF' => 'INT123',
            'Original transaction TRTYPE' => '1',
            'Timestamp' => '20260918103045',
            'Nonce' => Factory::nonce(),
            'P_SIGN' => 'abc',
        ]));

        $status = $this->client->getTransactionStatus(AuthorizationType::Direct, '123456');

        // Bu endpoint boşluqlu, insan üçün yazılmış açarlar qaytarır.
        $this->assertSame(Action::Success, $status->action());
        $this->assertTrue($status->isSuccessful());
        $this->assertSame('Approved', $status->statusMessage);
        $this->assertSame('4169********1234', $status->cardNumber);
        $this->assertSame('111111111111', $status->rrn);
        $this->assertSame('INT123', $status->internalReference);

        // Məbləğ JSON ədədi kimi gəlir; DTO sətir saxlayır.
        $this->assertSame('10.5', $status->amount);
    }

    public function testAnUnknownActionDoesNotBreakValidation(): void
    {
        $this->transport->queue(Response::json(['ACTION' => 99]));

        $status = $this->client->getTransactionStatus(AuthorizationType::Direct, '123456');

        $this->assertSame(99, $status->action);
        $this->assertNull($status->action());
        $this->assertFalse($status->isSuccessful());
    }

    public function testTheTransferResultExposesTheStatusCode(): void
    {
        $this->transport->queue(Response::json([
            'OperationId' => 'OP123',
            'SRN' => 'SRN001',
            'RRN' => 'RRN001',
            'Amount' => 10.5,
            'Cur' => '944',
            'ReceiverPan' => '4169********1234',
            'Status' => 'completed',
            'Timestamp' => '20260918103045',
            'ResponseCode' => '0',
            'Message' => 'OK',
            'Signature' => 'x',
        ]));

        $result = $this->client->confirmTransfer('Shop', 'SRN001', 10.5, '944');

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(TransferStatusCode::Success, $result->code());
        $this->assertSame('OP123', $result->operationId);
        $this->assertSame('4169********1234', $result->receiverPan);
    }

    public function testTheTransferStatusCodeIsStringBackedNotInt(): void
    {
        // Python-da `class TransferStatusCode(str, Enum)` yazılıb, dəyərlər isə ədəd
        // literallarıdır — `str` mixin-i onları sətrə çevirir. Yəni məftildə `'106'`
        // gedir, `106` yox.
        $this->assertSame('106', TransferStatusCode::SignatureError->value);

        $this->transport->queue(Response::json(['ResponseCode' => '106', 'Message' => 'bad signature']));

        $result = $this->client->declineTransfer('Shop', 'SRN001', 1, '944');

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(TransferStatusCode::SignatureError, $result->code());
    }

    public function testAConfirmResultSignatureIsVerifiedAgainstTheRightFields(): void
    {
        $values = ['OP123', 'SRN001', 'RRN001', '10.5', '944', '4169', 'completed', '20260918103045', '0', 'OK'];

        $this->transport->queue(Response::json([
            'OperationId' => 'OP123',
            'SRN' => 'SRN001',
            'RRN' => 'RRN001',
            'Amount' => '10.5',
            'Cur' => '944',
            'ReceiverPan' => '4169',
            'Status' => 'completed',
            'Timestamp' => '20260918103045',
            'ResponseCode' => '0',
            'Message' => 'OK',
            'Signature' => Factory::transferSignature($values),
        ]));

        $result = $this->client->confirmTransfer('Shop', 'SRN001', 10.5, '944');

        $this->assertSame($result, Factory::callback()->verifyTransferResult($result));
    }

    public function testADeclineResultSignsFewerFields(): void
    {
        // Rədd cavabında `rrn` və `receiverPan` iştirak etmir.
        $values = ['OP123', 'SRN001', '10.5', '944', 'declined', '20260918103045', '0', 'OK'];

        $this->transport->queue(Response::json([
            'OperationId' => 'OP123',
            'SRN' => 'SRN001',
            'Amount' => '10.5',
            'Cur' => '944',
            'Status' => 'declined',
            'Timestamp' => '20260918103045',
            'ResponseCode' => '0',
            'Message' => 'OK',
            'Signature' => Factory::transferSignature($values),
        ]));

        $result = $this->client->declineTransfer('Shop', 'SRN001', 10.5, '944');

        $this->assertSame($result, Factory::callback()->verifyTransferResult($result, confirmed: false));
    }

    public function testAForgedResultSignatureIsRefused(): void
    {
        $this->transport->queue(Response::json([
            'OperationId' => 'OP123',
            'SRN' => 'SRN001',
            'ResponseCode' => '0',
            'Message' => 'OK',
            'Signature' => 'not-the-signature',
        ]));

        $result = $this->client->declineTransfer('Shop', 'SRN001', 10.5, '944');

        // Python bunu `assert` ilə yoxlayır — `python -O` altında yoxlama tamamilə
        // yox olur. Burada adi koddur.
        $this->expectException(SignatureMismatch::class);

        Factory::callback()->verifyTransferResult($result, confirmed: false);
    }
}
