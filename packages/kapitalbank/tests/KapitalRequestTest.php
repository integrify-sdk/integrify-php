<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Tests;

use Integrify\Environment;
use Integrify\Exception\InvalidRequest;
use Integrify\Http\RecordingTransport;
use Integrify\Kapitalbank\KapitalClient;
use Integrify\Kapitalbank\KapitalConfig;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Klientin **göndərdiyi** sorğu: zərf, auth header-i, yol və payload field-ləri.
 */
final class KapitalRequestTest extends TestCase
{
    private RecordingTransport $transport;

    private KapitalClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::client($this->transport);
    }

    private function queueOrder(): void
    {
        $this->transport->queue(Response::json(Factory::createdOrder()));
    }

    private function queueTran(): void
    {
        $this->transport->queue(Response::json(Factory::tran(['pmoResultCode' => '1'])));
    }

    public function testCreateOrderPostsTheOrderEnvelope(): void
    {
        $this->queueOrder();

        $this->client->createOrder(10.5, 'AZN', 'D');

        $request = $this->transport->lastRequest();

        $this->assertSame('POST', $request->method);
        $this->assertSame('https://txpgtst.kapitalbank.az/api/order', $request->uri);
        $this->assertIsArray($request->body);
        $this->assertSame(['order'], array_keys($request->body));
    }

    public function testEveryRequestCarriesBasicAuth(): void
    {
        $this->queueOrder();

        $this->client->createOrder(1, 'AZN', 'D');

        $headers = $this->transport->lastRequest()->headers;

        $this->assertSame(
            'Basic ' . base64_encode(Factory::USERNAME . ':' . Factory::PASSWORD),
            $headers['Authorization'],
        );
        $this->assertSame('application/json', $headers['Content-Type']);
    }

    public function testTheProductionEnvironmentChangesTheHost(): void
    {
        $transport = new RecordingTransport();
        $client = Factory::client($transport, Factory::config(Environment::Prod));

        $transport->queue(Response::json(Factory::createdOrder()));
        $client->createOrder(1, 'AZN', 'D');

        $this->assertSame('https://e-commerce.kapitalbank.az/api/order', $transport->lastRequest()->uri);
    }

    public function testCreateOrderSendsTheCamelCaseFieldNames(): void
    {
        $this->queueOrder();

        $this->client->createOrder(10.5, 'AZN', 'D');

        $payload = Factory::sent($this->transport->lastRequest()->body);

        $this->assertSame('10.5', $payload['amount']);
        $this->assertSame('AZN', $payload['currency']);
        $this->assertSame('D', $payload['description']);
        $this->assertSame('az', $payload['language']);
        $this->assertSame('Order_SMS', $payload['typeRid']);
        $this->assertSame(['Cit'], $payload['hppCofCapturePurposes']);
    }

    public function testNullFieldsStayInThePayload(): void
    {
        $this->queueOrder();

        $this->client->createOrder(1, 'AZN', 'D');

        $payload = Factory::sent($this->transport->lastRequest()->body);

        // Python `exclude_none` vermir, yəni `null`-lar məftildə gedir.
        $this->assertTrue(array_key_exists('hppRedirectUrl', $payload));
        $this->assertNull($payload['hppRedirectUrl']);
    }

    public function testTheRedirectUrlComesFromTheConfiguration(): void
    {
        $transport = new RecordingTransport();
        $client = Factory::client($transport, Factory::config(redirectUrl: 'https://shop.az/done'));

        $transport->queue(Response::json(Factory::createdOrder()));
        $client->createOrder(1, 'AZN', 'D');

        $this->assertSame(
            'https://shop.az/done',
            Factory::sent($transport->lastRequest()->body)['hppRedirectUrl'],
        );
    }

    public function testTheOrderTypesDifferPerMethod(): void
    {
        $this->queueOrder();
        $this->client->createOrder(1, 'AZN', 'D');
        $payload = Factory::sent($this->transport->lastRequest()->body);
        $this->assertSame('Order_SMS', $payload['typeRid']);
        $this->assertFalse(array_key_exists('aut', $payload) && $payload['aut'] !== null);

        $this->queueOrder();
        $this->client->saveCard(1, 'AZN', 'D');
        $payload = Factory::sent($this->transport->lastRequest()->body);
        $this->assertSame('Order_DMS', $payload['typeRid']);
        $this->assertSame(['Cit', 'Recurring'], $payload['hppCofCapturePurposes']);
        $this->assertSame(['purpose' => 'AddCard'], $payload['aut']);

        $this->queueOrder();
        $this->client->payAndSaveCard(1, 'AZN', 'D');
        $payload = Factory::sent($this->transport->lastRequest()->body);
        $this->assertSame('Order_SMS', $payload['typeRid']);
        $this->assertSame(['Cit'], $payload['hppCofCapturePurposes']);
        $this->assertSame(['purpose' => 'AddCard'], $payload['aut']);
    }

    public function testOrderWithSavedCardSendsNoRedirectUrlOrCapturePurposes(): void
    {
        $transport = new RecordingTransport();
        // Konfiqurasiyada redirect URL olsa belə bu endpoint onu göndərməməlidir.
        $client = Factory::client($transport, Factory::config(redirectUrl: 'https://shop.az/done'));

        $transport->queue(Response::json(Factory::createdOrder()));
        $client->orderWithSavedCard(1, 'AZN', 'D');

        $payload = Factory::sent($transport->lastRequest()->body);

        $this->assertSame('Order_REC', $payload['typeRid']);
        $this->assertNull($payload['hppRedirectUrl']);
        $this->assertNull($payload['hppCofCapturePurposes']);
    }

    public function testGetOrderInformationUsesGetAndThePathParameter(): void
    {
        $this->transport->queue(Response::json(Factory::orderInformation()));

        $this->client->getOrderInformation(1231);

        $request = $this->transport->lastRequest();

        $this->assertSame('GET', $request->method);
        $this->assertSame('https://txpgtst.kapitalbank.az/api/order/1231', $request->uri);
        $this->assertNull($request->body);
        $this->assertSame([], $request->query);
    }

    public function testTheDetailLevelsAreQueryParametersNotPartOfThePath(): void
    {
        $this->transport->queue(Response::json(Factory::order(['id' => 1231])));

        $this->client->getDetailedOrderInformation(1231);

        $request = $this->transport->lastRequest();

        // `Client::uri()` path-də `?` görəndə `InvalidRequest` atır, ona görə detal
        // səviyyələri query kimi gedir.
        $this->assertSame('https://txpgtst.kapitalbank.az/api/order/1231', $request->uri);
        $this->assertSame(2, $request->query['tranDetailLevel']);
        $this->assertSame(2, $request->query['tokenDetailLevel']);
        $this->assertSame(2, $request->query['orderDetailLevel']);
    }

    public function testTheTransactionEndpointsSharePathAndDifferInPayload(): void
    {
        $expected = 'https://txpgtst.kapitalbank.az/api/order/1231/exec-tran';

        $this->queueTran();
        $this->client->refundOrder(1231, 5);
        $this->assertSame($expected, $this->transport->lastRequest()->uri);
        $payload = Factory::sent($this->transport->lastRequest()->body, 'tran');
        $this->assertSame(['amount' => '5', 'phase' => 'Single', 'type' => 'Refund'], $payload);

        $this->queueTran();
        $this->client->fullReverseOrder(1231);
        $this->assertSame($expected, $this->transport->lastRequest()->uri);
        $payload = Factory::sent($this->transport->lastRequest()->body, 'tran');
        $this->assertSame(['phase' => 'Auth', 'voidKind' => 'Full'], $payload);

        $this->queueTran();
        $this->client->clearingOrder(1231, 5);
        $payload = Factory::sent($this->transport->lastRequest()->body, 'tran');
        $this->assertSame(['amount' => '5', 'phase' => 'Clearing'], $payload);

        $this->queueTran();
        $this->client->partialReverseOrder(1231, 5);
        $payload = Factory::sent($this->transport->lastRequest()->body, 'tran');
        $this->assertSame(['amount' => '5', 'phase' => 'Single', 'voidKind' => 'Partial'], $payload);
    }

    public function testFullReverseSendsNoAmount(): void
    {
        $this->queueTran();

        $this->client->fullReverseOrder(1231);

        // Tam ləğv bütün bloklanmış məbləği azad edir; məbləğ göndərmək mənasızdır.
        $this->assertFalse(array_key_exists('amount', Factory::sent($this->transport->lastRequest()->body, 'tran')));
    }

    public function testLinkCardTokenSendsTwoEnvelopesAndThePasswordAsQuery(): void
    {
        $this->transport->queue(Response::json(Factory::order(['status' => 'Linked'])));

        $this->client->linkCardToken(999, 1231, 'order-secret');

        $request = $this->transport->lastRequest();

        $this->assertSame('https://txpgtst.kapitalbank.az/api/order/1231/set-src-token', $request->uri);
        $this->assertSame('order-secret', $request->query['password']);
        $this->assertSame([
            'order' => ['initiationEnvKind' => 'Server'],
            'token' => ['storedId' => 999],
        ], $request->body);
    }

    public function testProcessPaymentWithSavedCardSendsTheConditions(): void
    {
        $this->queueTran();

        $this->client->processPaymentWithSavedCard(1, 1231, 'order-secret');

        $request = $this->transport->lastRequest();
        $payload = Factory::sent($request->body, 'tran');

        $this->assertSame('https://txpgtst.kapitalbank.az/api/order/1231/exec-tran', $request->uri);
        $this->assertSame('order-secret', $request->query['password']);
        $this->assertSame(['amount' => '1', 'phase' => 'Single', 'conditions' => ['cofUsage' => 'Cit']], $payload);
    }

    public function testPayWithSavedCardChainsThreeRequests(): void
    {
        $this->transport->queue(
            Response::json(Factory::createdOrder()),
            Response::json(Factory::order(['status' => 'Linked'])),
            Response::json(Factory::tran(['pmoResultCode' => '1', 'approvalCode' => '12345'])),
        );

        $result = $this->client->payWithSavedCard(999, 1, 'AZN', 'D');

        $requests = $this->transport->requests();

        $this->assertCount(3, $requests);
        $this->assertSame('https://txpgtst.kapitalbank.az/api/order', $requests[0]->uri);
        $this->assertSame('https://txpgtst.kapitalbank.az/api/order/1231/set-src-token', $requests[1]->uri);
        $this->assertSame('https://txpgtst.kapitalbank.az/api/order/1231/exec-tran', $requests[2]->uri);

        // Sifarişin öz parolu ikinci və üçüncü sorğuya ötürülür, merchant parolu deyil.
        $this->assertSame('1231231', $requests[1]->query['password']);
        $this->assertSame('1231231', $requests[2]->query['password']);

        $this->assertSame('12345', $result->approvalCode);
    }

    public function testTheOrderPasswordIsNotTheMerchantPassword(): void
    {
        $this->transport->queue(
            Response::json(Factory::createdOrder()),
            Response::json(Factory::order(['status' => 'Linked'])),
            Response::json(Factory::tran(['pmoResultCode' => '1'])),
        );

        $this->client->payWithSavedCard(999, 1, 'AZN', 'D');

        foreach ($this->transport->requests() as $request) {
            $this->assertNotSame(Factory::PASSWORD, $request->query['password'] ?? null);
        }
    }

    public function testAmountsAreSentAsStrings(): void
    {
        $this->queueOrder();
        $this->client->createOrder(10, 'AZN', 'D');
        $this->assertSame('10', Factory::sent($this->transport->lastRequest()->body)['amount']);

        $this->queueOrder();
        $this->client->createOrder('10.50', 'AZN', 'D');
        $this->assertSame('10.50', Factory::sent($this->transport->lastRequest()->body)['amount']);

        $this->queueOrder();
        $this->client->createOrder(0.1 + 0.2, 'AZN', 'D');
        $this->assertSame('0.3', Factory::sent($this->transport->lastRequest()->body)['amount']);
    }

    public function testAFloatAmountIsNeverWrittenInScientificNotation(): void
    {
        $this->queueOrder();

        $this->client->createOrder(1.0e20, 'AZN', 'D');

        $amount = Factory::sent($this->transport->lastRequest()->body)['amount'];

        $this->assertIsString($amount);
        $this->assertFalse(str_contains(strtolower($amount), 'e'));
    }

    public function testANonNumericAmountIsRefused(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->createOrder('sonra', 'AZN', 'D');
    }

    public function testAScientificNotationStringIsRefused(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->createOrder('1e5', 'AZN', 'D');
    }

    public function testANonFiniteAmountIsRefused(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->refundOrder(1231, NAN);
    }

    public function testAnExplicitBaseUrlOverridesTheEnvironment(): void
    {
        $transport = new RecordingTransport();
        $client = new KapitalClient(
            new KapitalConfig('u', 'p', Environment::Prod),
            $transport,
            'https://staging.example.test',
        );

        $transport->queue(Response::json(Factory::createdOrder()));
        $client->createOrder(1, 'AZN', 'D');

        $this->assertSame('https://staging.example.test/api/order', $transport->lastRequest()->uri);
    }
}
