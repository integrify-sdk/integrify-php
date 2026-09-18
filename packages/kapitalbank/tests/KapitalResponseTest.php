<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Tests;

use Integrify\Exception\RequestFailed;
use Integrify\Http\RecordingTransport;
use Integrify\Http\Request;
use Integrify\Kapitalbank\Enum\ErrorCode;
use Integrify\Kapitalbank\Enum\OrderStatus;
use Integrify\Kapitalbank\Exception\RequestRejected;
use Integrify\Kapitalbank\KapitalClient;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Klientin **qaytardığı** DTO-lar və bankın xəta cavabları.
 */
final class KapitalResponseTest extends TestCase
{
    private RecordingTransport $transport;

    private KapitalClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::client($this->transport);
    }

    public function testCreateOrderUnwrapsTheOrderEnvelope(): void
    {
        $this->transport->queue(Response::json(Factory::createdOrder()));

        $order = $this->client->createOrder(1, 'AZN', 'D');

        $this->assertSame(1231, $order->id);
        $this->assertSame('1231231', $order->password);
        $this->assertSame('https://txpgtst.kapitalbank.az', $order->hppUrl);
    }

    public function testTheRedirectUrlIsBuiltFromThreeFields(): void
    {
        $this->transport->queue(Response::json(Factory::createdOrder()));

        $order = $this->client->createOrder(1, 'AZN', 'D');

        // Bank hazır link qaytarmır — yığmaq lazımdır.
        $this->assertSame(
            'https://txpgtst.kapitalbank.az?id=1231&password=1231231',
            $order->redirectUrl(),
        );
    }

    public function testTheRedirectUrlEncodesThePassword(): void
    {
        $this->transport->queue(Response::json(Factory::order([
            'id' => 7,
            'password' => 'a b&c=d',
            'hppUrl' => 'https://txpgtst.kapitalbank.az',
        ])));

        $order = $this->client->createOrder(1, 'AZN', 'D');

        // Kodlanmasa `&c=d` ayrıca query parametrinə çevrilərdi.
        $this->assertSame(
            'https://txpgtst.kapitalbank.az?id=7&password=a%20b%26c%3Dd',
            $order->redirectUrl(),
        );
    }

    public function testOrderInformationExposesTheStatus(): void
    {
        $this->transport->queue(Response::json(Factory::order([
            'id' => 1231,
            'typeRid' => 'Order_SMS',
            'status' => 'FullyPaid',
            'lastStatusLogin' => 'merchant',
            'amount' => 10.5,
            'currency' => 'AZN',
            'createTime' => '2026-09-17T10:00:00',
            'type' => ['title' => 'Purchase'],
        ])));

        $order = $this->client->getOrderInformation(1231);

        $this->assertSame(OrderStatus::FullyPaid, $order->status());
        $this->assertTrue($order->isFullyPaid());
        $this->assertSame('Purchase', $order->type?->title);

        // Bank məbləği JSON ədədi kimi qaytarır; DTO sətir saxlayır.
        $this->assertSame('10.5', $order->amount);
    }

    public function testAnUnknownStatusDoesNotBreakValidation(): void
    {
        $this->transport->queue(Response::json(Factory::order([
            'id' => 1,
            'typeRid' => 'Order_SMS',
            'status' => 'SomethingNew',
            'lastStatusLogin' => 'm',
            'amount' => 1,
            'currency' => 'AZN',
            'createTime' => 't',
        ])));

        $order = $this->client->getOrderInformation(1);

        $this->assertSame('SomethingNew', $order->status);
        $this->assertNull($order->status());
        $this->assertFalse($order->isFullyPaid());
    }

    public function testTheDetailedOrderExposesTheNestedCardToken(): void
    {
        $this->transport->queue(Response::json(Factory::order([
            'id' => 1231,
            'status' => 'FullyPaid',
            'amount' => 10.5,
            'currency' => 'AZN',
            'srcToken' => [
                'id' => 999,
                'paymentMethod' => 'Card',
                'displayName' => '4169 **** **** 1234',
                'card' => [
                    'expiration' => '12/28',
                    'brand' => 'VISA',
                    'authentication' => ['needCVV2' => true, 'needTds' => false],
                ],
            ],
            'merchant' => [
                'id' => 5,
                'title' => 'Shop',
                'businessAddress' => ['country' => 'Azerbaijan', 'countryA2' => 'AZ', 'countryN3' => 31],
            ],
        ])));

        $order = $this->client->getDetailedOrderInformation(1231);

        $this->assertSame(999, $order->cardToken());
        $this->assertTrue($order->isFullyPaid());

        // İç-içə DTO-lar addım-addım yoxlanılır: `?->` zəncirini bir sətirdə yazmaq
        // hansı səviyyənin `null` olduğunu gizlədir.
        $token = $order->srcToken;
        $this->assertNotNull($token);

        $card = $token->card;
        $this->assertNotNull($card);
        $this->assertSame('VISA', $card->brand);

        $authentication = $card->authentication;
        $this->assertNotNull($authentication);
        $this->assertTrue($authentication->needCvv2);

        $merchant = $order->merchant;
        $this->assertNotNull($merchant);
        $this->assertSame('AZ', $merchant->businessAddress?->countryA2);
    }

    public function testTheDetailedOrderSurvivesAMostlyEmptyBody(): void
    {
        // Bütün field-lər nullable-dir, ona görə qısa cavab da parse olunur.
        $this->transport->queue(Response::json(Factory::order(['id' => 1])));

        $order = $this->client->getDetailedOrderInformation(1);

        $this->assertSame(1, $order->id);
        $this->assertNull($order->cardToken());
        $this->assertNull($order->status());
    }

    public function testTheDetailedOrderParsesTheStoredTokenList(): void
    {
        $this->transport->queue(Response::json(Factory::order([
            'id' => 1,
            'storedTokens' => [
                ['id' => 11, 'cofProviderRid' => 'p1'],
                ['id' => 22, 'cofProviderRid' => 'p2'],
            ],
        ])));

        $order = $this->client->getDetailedOrderInformation(1);

        $this->assertCount(2, $order->storedTokens ?? []);
        $this->assertSame(22, $order->storedTokens[1]->id ?? null);
    }

    public function testTransactionResultKeepsTheRawPmoCodeAndAddsAMessage(): void
    {
        $this->transport->queue(Response::json(Factory::tran([
            'match' => ['tranActionId' => 'a1', 'ridByPmo' => 'r1'],
            'pmoResultCode' => '1',
            'approvalCode' => '12345',
        ])));

        $result = $this->client->refundOrder(1231, 5);

        // Python kodun özünü mesajla əvəz edir; burada ikisi də var.
        $this->assertSame('1', $result->pmoResultCode);
        $this->assertSame('Approved', $result->resultMessage());
        $this->assertTrue($result->isApproved());
        $this->assertSame('a1', $result->match?->tranActionId);
    }

    public function testAnUnknownPmoCodeDoesNotBreakTheResponse(): void
    {
        // Python-da `PMO_RESULT_CODES[v]` tanınmayan kod üçün `KeyError` atır, yəni
        // bank yeni kod qaytaran gün cavabın parse-i tamamilə sınır.
        $this->transport->queue(Response::json(Factory::tran(['pmoResultCode' => '997'])));

        $result = $this->client->refundOrder(1231, 5);

        $this->assertSame('997', $result->pmoResultCode);
        $this->assertNull($result->resultMessage());
        $this->assertFalse($result->isApproved());
    }

    public function testADeclinedTransactionIsNotApproved(): void
    {
        $this->transport->queue(Response::json(Factory::tran(['pmoResultCode' => '59'])));

        $result = $this->client->refundOrder(1231, 5);

        $this->assertSame('Insufficient funds', $result->resultMessage());
        $this->assertFalse($result->isApproved());
    }

    public function testLinkCardTokenUnwrapsTheOrderEnvelope(): void
    {
        $this->transport->queue(Response::json(Factory::order([
            'status' => 'Linked',
            'cvv2AuthStatus' => 'N',
            'tdsV1AuthStatus' => 'N',
            'tdsV2AuthStatus' => 'Y',
            'otpAutStatus' => 'N',
            'srcToken' => ['id' => 999, 'displayName' => '4169 **** **** 1234'],
        ])));

        $link = $this->client->linkCardToken(999, 1231, 'secret');

        $this->assertSame('Linked', $link->status);
        $this->assertSame('Y', $link->tdsV2AuthStatus);
        $this->assertSame(999, $link->srcToken?->id);
    }

    public function testABankErrorBecomesRequestRejected(): void
    {
        $this->transport->queue(Response::json([
            'errorCode' => 'InvalidOrderState',
            'errorDescription' => 'no order found',
        ], status: 500));

        try {
            $this->client->getOrderInformation(1231);
            $this->fail('expected RequestRejected');
        } catch (RequestRejected $rejection) {
            $this->assertSame('InvalidOrderState', $rejection->errorCode);
            $this->assertSame(ErrorCode::InvalidOrderState, $rejection->code());
            $this->assertSame('no order found', $rejection->description);
            $this->assertSame(500, $rejection->getCode());
            $this->assertInstanceOf(RequestFailed::class, $rejection->getPrevious());
        }
    }

    public function testTheRejectionCarriesTheErrorDetails(): void
    {
        $this->transport->queue(Response::json([
            'errorCode' => 'InvalidAmt',
            'errorDescription' => 'Invalid amount',
            'errorDetails' => ['field' => 'amount'],
        ], status: 400));

        try {
            $this->client->refundOrder(1231, 5);
            $this->fail('expected RequestRejected');
        } catch (RequestRejected $rejection) {
            $this->assertSame(ErrorCode::InvalidAmount, $rejection->code());
            $this->assertSame(['field' => 'amount'], $rejection->details);
        }
    }

    public function testAnUnknownErrorCodeStillRaisesWithTheRawValue(): void
    {
        $this->transport->queue(Response::json([
            'errorCode' => 'SomethingNew',
            'errorDescription' => 'who knows',
        ], status: 500));

        try {
            $this->client->getOrderInformation(1);
            $this->fail('expected RequestRejected');
        } catch (RequestRejected $rejection) {
            $this->assertSame('SomethingNew', $rejection->errorCode);
            $this->assertNull($rejection->code());
        }
    }

    public function testAnUnparseableErrorBodyStillRaises(): void
    {
        // Gateway HTML xəta səhifəsi qaytara bilər; status kodu özü kifayətdir.
        $this->transport->queue(new Response(502, [], '<html>Bad Gateway</html>'));

        try {
            $this->client->getOrderInformation(1);
            $this->fail('expected RequestRejected');
        } catch (RequestRejected $rejection) {
            $this->assertNull($rejection->errorCode);
            $this->assertNull($rejection->description);
            $this->assertSame(502, $rejection->getCode());
        }
    }

    public function testANetworkFailureStaysRequestFailed(): void
    {
        // Şəbəkə xətası bankın rəddi deyil — iki fərqli problem eyni tipə yığılmamalıdır.
        $this->transport->queue(RequestFailed::transportError(
            new Request('GET', 'https://txpgtst.kapitalbank.az/api/order/1'),
            new \RuntimeException('connection reset'),
        ));

        $this->expectException(RequestFailed::class);

        $this->client->getOrderInformation(1);
    }
}
