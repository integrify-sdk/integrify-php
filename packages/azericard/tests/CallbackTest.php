<?php

declare(strict_types=1);

namespace Integrify\Azericard\Tests;

use Integrify\Azericard\Enum\Action;
use Integrify\Azericard\Enum\CardStatus;
use Integrify\Azericard\Exception\SignatureMismatch;
use Integrify\Exception\MissingConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * Callback-lərin oxunması.
 *
 * İki callback var və təhlükəsizlik baxımından eyni deyillər: kart callback-inin
 * imzası yoxlanıla bilmir, köçürmə callback-ininki isə bilir.
 */
final class CallbackTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function transferData(string $key = Factory::TRANSFER_KEY): array
    {
        $values = [
            'OP1234567890123456',
            'SRN001',
            '10.5',
            '944',
            'our_active',
            '4169123456781234',
            'pending',
            '20260918103045',
            '00',
            'OK',
        ];

        return [
            'OperationID' => $values[0],
            'SRN' => $values[1],
            'Amount' => $values[2],
            'Cur' => $values[3],
            'CardStatus' => $values[4],
            'ReceiverPAN' => $values[5],
            'Status' => $values[6],
            'Timestamp' => $values[7],
            'Response Code' => $values[8],
            'Message' => $values[9],
            'Signature' => Factory::transferSignature($values, $key),
        ];
    }

    public function testAnAuthCallbackDecodesIntoTheDto(): void
    {
        $data = Factory::callback()->decodeAuth([
            'ORDER' => '123456',
            'AMOUNT' => '10.5',
            'CURRENCY' => '944',
            'TERMINAL' => Factory::MERCHANT_ID,
            'TRTYPE' => '1',
            'ACTION' => 0,
            'RC' => '00',
            'APPROVAL' => '123456',
            'RRN' => '111111111111',
            'INT_REF' => 'INT123',
            'TIMESTAMP' => '20260918103045',
            'NONCE' => Factory::nonce(),
            'P_SIGN' => 'abc',
        ]);

        $this->assertTrue($data->isSuccessful());
        $this->assertSame(Action::Success, $data->action());
        $this->assertSame('123456', $data->order);

        // `finalize()` üçün lazım olan iki dəyər.
        $this->assertSame('111111111111', $data->rrn);
        $this->assertSame('INT123', $data->internalReference);
    }

    public function testAnAuthCallbackIsAcceptedWithoutSignatureVerification(): void
    {
        // Bu qəsdəndir və sənədləşdirilib: `P_SIGN` Azericard-ın açarı ilə imzalanıb,
        // yoxlamaq üçün onların PUBLIC açarı lazımdır — bizdə yalnız öz private
        // açarımız var. Python kitabxanası da yoxlamır.
        //
        // Ona görə callback tək başına "ödəniş oldu" sübutu deyil:
        // `getTransactionStatus()` ilə təsdiqlənməlidir.
        $data = Factory::callback()->decodeAuth([
            'ORDER' => '123456',
            'ACTION' => 0,
            'P_SIGN' => 'obviously-not-a-real-signature',
        ]);

        $this->assertTrue($data->isSuccessful());
        $this->assertSame('obviously-not-a-real-signature', $data->signature);
    }

    public function testACardSavingCallbackCarriesTheToken(): void
    {
        $data = Factory::callback()->decodeAuth([
            'ORDER' => '123456',
            'ACTION' => 0,
            'CARD' => '4169********1234',
            'TOKEN' => str_repeat('T', 28),
        ]);

        $this->assertSame(str_repeat('T', 28), $data->token);
        $this->assertSame('4169********1234', $data->card);
    }

    public function testAFailedAuthCallbackIsNotSuccessful(): void
    {
        $data = Factory::callback()->decodeAuth(['ORDER' => '1', 'ACTION' => 2, 'RC' => '05']);

        $this->assertFalse($data->isSuccessful());
        $this->assertSame(Action::Cancelled, $data->action());
    }

    public function testAnUnknownActionDoesNotBreakTheCallback(): void
    {
        $data = Factory::callback()->decodeAuth(['ORDER' => '1', 'ACTION' => 99]);

        $this->assertSame(99, $data->action);
        $this->assertNull($data->action());
        $this->assertFalse($data->isSuccessful());
    }

    public function testAValidTransferCallbackDecodes(): void
    {
        $data = Factory::callback()->decodeTransfer(self::transferData());

        $this->assertSame('OP1234567890123456', $data->operationId);
        $this->assertSame(CardStatus::Active, $data->cardStatus());
        $this->assertSame('pending', $data->status);
    }

    public function testAForgedTransferCallbackIsRefused(): void
    {
        $data = self::transferData();
        $data['Signature'] = 'not-the-signature';

        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decodeTransfer($data);
    }

    public function testATamperedAmountIsRefused(): void
    {
        // İmza düzgündür, lakin məbləğ dəyişdirilib — imza artıq uyğun gəlmir.
        $data = self::transferData();
        $data['Amount'] = '9999';

        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decodeTransfer($data);
    }

    public function testACallbackSignedWithAnotherKeyIsRefused(): void
    {
        $data = self::transferData(key: 'another-key');

        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decodeTransfer($data);
    }

    public function testAMissingSignatureIsRefused(): void
    {
        $data = self::transferData();
        unset($data['Signature']);

        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decodeTransfer($data);
    }

    public function testDecodingATransferWithoutAKeyIsReported(): void
    {
        $callback = Factory::callback(Factory::config(transferKey: null));

        $this->expectException(MissingConfiguration::class);

        $callback->decodeTransfer(self::transferData());
    }

    public function testAnUnknownCardStatusDoesNotBreakTheCallback(): void
    {
        $values = ['OP1', 'SRN001', '1', '944', 'brand_new', '4169', 'pending', '20260918103045', '00', 'OK'];

        $data = Factory::callback()->decodeTransfer([
            'OperationID' => $values[0],
            'SRN' => $values[1],
            'Amount' => $values[2],
            'Cur' => $values[3],
            'CardStatus' => $values[4],
            'ReceiverPAN' => $values[5],
            'Status' => $values[6],
            'Timestamp' => $values[7],
            'Response Code' => $values[8],
            'Message' => $values[9],
            'Signature' => Factory::transferSignature($values),
        ]);

        $this->assertSame('brand_new', $data->cardStatus);
        $this->assertNull($data->cardStatus());
    }
}
