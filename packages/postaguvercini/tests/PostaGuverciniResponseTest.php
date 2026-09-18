<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Tests;

use Integrify\Http\RecordingTransport;
use Integrify\PostaGuvercini\Dto\Request\SmsMessage;
use Integrify\PostaGuvercini\Enum\StatusCode;
use Integrify\PostaGuvercini\PostaGuverciniClient;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Klientin **qaytardığı** DTO-lar.
 *
 * Mock payload-lar Python kitabxanasının `tests/mocks.py` faylından götürülüb.
 */
final class PostaGuverciniResponseTest extends TestCase
{
    private RecordingTransport $transport;

    private PostaGuverciniClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::client($this->transport);
    }

    public function testSendSmsReturnsTheSentMessages(): void
    {
        $this->transport->queue(Response::json(Factory::sent()));

        $result = $this->client->sendSms('Salam', ['994123456789']);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(StatusCode::Ok, $result->status());
        $this->assertCount(1, $result->messages());
        $this->assertSame('1234', $result->messages()[0]->messageId);
        $this->assertSame('994123456789', $result->messages()[0]->receiver);
        $this->assertSame(1, $result->messages()[0]->charge);
    }

    public function testMessageIdsFeedTheStatusRequestDirectly(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(result: [
            ['MessageId' => '1234', 'Receiver' => '994123456789', 'Charge' => 1],
            ['MessageId' => '1235', 'Receiver' => '994123456780', 'Charge' => 1],
        ])));

        $result = $this->client->sendMessages([
            new SmsMessage('994123456789', 'A'),
            new SmsMessage('994123456780', 'B'),
        ]);

        $this->assertSame(['1234', '1235'], $result->messageIds());
    }

    public function testAServiceErrorArrivesAsHttp200(): void
    {
        // Servis xəta halında da 200 qaytarır, ona görə `RequestFailed` atılmır —
        // nəticəni yalnız `isSuccessful()` verir.
        $this->transport->queue(Response::json(
            Factory::envelope(1060, description: 'Empty receiver list'),
        ));

        $result = $this->client->sendSms('Salam', ['994123456789']);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(StatusCode::EmptyReceiverList, $result->status());
        $this->assertSame('Empty receiver list', $result->statusDescription);
    }

    public function testAFailedSendYieldsAnEmptyMessageListNotNull(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(1090, description: 'Message empty')));

        $result = $this->client->sendSms('', ['994123456789']);

        // `$result` `null`-dur, `messages()` isə `foreach` üçün həmişə massiv verir.
        $this->assertNull($result->result);
        $this->assertSame([], $result->messages());
        $this->assertSame([], $result->messageIds());
    }

    public function testAnUnknownStatusCodeDoesNotBreakValidation(): void
    {
        // DTO xam `int` saxlayır, ona görə servis yeni kod əlavə etsə validasiya
        // sınmır — `status()` sadəcə `null` qaytarır.
        $this->transport->queue(Response::json(Factory::envelope(9999, description: 'Who knows')));

        $result = $this->client->sendSms('Salam', ['994123456789']);

        $this->assertSame(9999, $result->statusCode);
        $this->assertNull($result->status());
        $this->assertFalse($result->isSuccessful());
    }

    public function testTheStatusResponseParsesEveryField(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(result: [
            [
                'MessageId' => '1234',
                'Receiver' => '994123456789',
                'SmsStatus' => '1',
                'SmsStatusDescription' => 'Delivered',
                'IsFinalStatus' => '1',
                'StatusTime' => '20260918 10:31',
                'SmsCharge' => '1',
            ],
        ])));

        $result = $this->client->getStatus(['1234']);

        $this->assertTrue($result->isSuccessful());
        $this->assertCount(1, $result->statuses());

        $status = $result->statuses()[0];
        $this->assertSame('Delivered', $status->smsStatusDescription);
        $this->assertTrue($status->isFinal());

        // Servis bunları sətir kimi qaytarır, ona görə DTO da sətir saxlayır.
        $this->assertSame('1', $status->smsCharge);
        $this->assertSame('1', $status->isFinalStatus);
    }

    public function testIsFinalAcceptsTheSpellingsTheServiceMightUse(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(result: [
            ['MessageId' => '1', 'IsFinalStatus' => 'true'],
            ['MessageId' => '2', 'IsFinalStatus' => 'True'],
            ['MessageId' => '3', 'IsFinalStatus' => '0'],
            ['MessageId' => '4', 'IsFinalStatus' => ''],
        ])));

        $statuses = $this->client->getStatus(['1'])->statuses();

        $this->assertTrue($statuses[0]->isFinal());
        $this->assertTrue($statuses[1]->isFinal());
        // Tanınmayan və boş dəyər "hələ yekun deyil" sayılır — yenidən soruşmağa
        // aparır, səhvən "bitdi" deməkdən təhlükəsizdir.
        $this->assertFalse($statuses[2]->isFinal());
        $this->assertFalse($statuses[3]->isFinal());
    }

    public function testAMissingFinalFlagIsNotFinal(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(result: [['MessageId' => '1']])));

        $this->assertFalse($this->client->getStatus(['1'])->statuses()[0]->isFinal());
    }

    public function testCheckBalanceUnwrapsTheNestedObject(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(result: ['Balance' => 42])));

        $result = $this->client->checkBalance();

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(42, $result->balance());
    }

    public function testAZeroBalanceIsNotTheSameAsAnUnknownOne(): void
    {
        $this->transport->queue(Response::json(Factory::envelope(result: ['Balance' => 0])));
        $this->assertSame(0, $this->client->checkBalance()->balance());

        $this->transport->queue(Response::json(Factory::envelope(7040, description: 'No record')));
        $failed = $this->client->checkBalance();

        $this->assertFalse($failed->isSuccessful());
        // `0` "kredit bitib", `null` isə "bilinmir" — ikisini qarışdırmaq balansı
        // olan hesabı bitmiş kimi göstərərdi.
        $this->assertNull($failed->balance());
    }

    public function testTheStatusEnumCoversTheServicesCodes(): void
    {
        $this->assertSame(22, count(StatusCode::cases()));
        $this->assertTrue(StatusCode::Ok->isSuccessful());
        $this->assertFalse(StatusCode::BadRequest->isSuccessful());
        $this->assertSame(1060, StatusCode::EmptyReceiverList->value);

        // Python-da 7020 iki adla elan olunub (`CREDIT_INQUIRY_DB_ERROR` və
        // `ALPHANUMBERIC_QUERY_DB_ERROR`); PHP-də təkrarlanan dəyər fatal error-dur,
        // ona görə burada bir case var.
        $this->assertSame(StatusCode::CreditInquiryDbError, StatusCode::from(7020));
    }
}
