<?php

declare(strict_types=1);

namespace Integrify\EPoint\Tests;

use Integrify\EPoint\EPointClient;
use Integrify\Exception\InvalidRequest;
use Integrify\Http\RecordingTransport;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Klientin **göndərdiyi** sorğu: zərf, imza və payload field-ləri.
 */
final class EPointRequestTest extends TestCase
{
    private RecordingTransport $transport;

    private EPointClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::client($this->transport);
    }

    private function ok(): void
    {
        $this->transport->queue(Response::json(['status' => 'success']));
    }

    public function testPayPostsTheEnvelopeToTheRequestEndpoint(): void
    {
        $this->ok();

        $this->client->pay(100, 'AZN', 'order-1');

        $request = $this->transport->lastRequest();

        $this->assertSame('POST', $request->method);
        $this->assertSame('https://epoint.az/api/1/request', $request->uri);

        // Məftildə yalnız zərf gedir — payload field-ləri birbaşa görünmür.
        $this->assertIsArray($request->body);
        $this->assertSame(['data', 'signature'], array_keys($request->body));
    }

    public function testTheSignatureMatchesEPointsFormula(): void
    {
        $this->ok();

        $this->client->pay(100, 'AZN', 'order-1');

        $body = $this->transport->lastRequest()->body;
        $this->assertIsArray($body);

        $data = $body['data'];
        $this->assertIsString($data);

        $this->assertSame(Factory::signature($data), $body['signature']);
    }

    public function testThePrivateKeyIsNeverSent(): void
    {
        $this->ok();

        $this->client->pay(100, 'AZN', 'order-1', description: 'Ödəniş');

        $request = $this->transport->lastRequest();
        $wire = (string) json_encode([$request->body, $request->query, $request->headers, $request->uri]);

        // Private key yalnız imzanın hesablanmasında iştirak edir. Base64-lənmiş
        // payload-da da axtarılır, çünki zərf onu "gizlətmir".
        $this->assertFalse(str_contains($wire, Factory::PRIVATE_KEY));
        $this->assertFalse(str_contains((string) json_encode(Factory::sent($request->body)), Factory::PRIVATE_KEY));
    }

    public function testEveryPayloadCarriesThePublicKeyAndLanguage(): void
    {
        $this->ok();

        $this->client->pay(100, 'AZN', 'order-1');

        $payload = Factory::sent($this->transport->lastRequest()->body);

        $this->assertSame(Factory::PUBLIC_KEY, $payload['public_key']);
        $this->assertSame('az', $payload['language']);
    }

    public function testPaySendsTheApiFieldNames(): void
    {
        $this->ok();

        $this->client->pay(100, 'AZN', 'order-1', description: 'Ödəniş', otherAttr: ['cart' => 7]);

        $payload = Factory::sent($this->transport->lastRequest()->body);

        $this->assertSame('100', $payload['amount']);
        $this->assertSame('AZN', $payload['currency']);
        $this->assertSame('order-1', $payload['order_id']);
        $this->assertSame('Ödəniş', $payload['description']);
        $this->assertSame(['cart' => 7], $payload['other_attr']);
    }

    public function testNullFieldsStayInThePayload(): void
    {
        $this->ok();

        $this->client->pay(100, 'AZN', 'order-1');

        $payload = Factory::sent($this->transport->lastRequest()->body);

        // Python kitabxanası `exclude_none` vermir, yəni `null`-lar məftildə gedir.
        // Onları "təmizləmək" iki SDK-nın payload-unu ayırardı.
        $this->assertTrue(array_key_exists('description', $payload));
        $this->assertNull($payload['description']);
        $this->assertTrue(array_key_exists('success_redirect_url', $payload));
        $this->assertNull($payload['success_redirect_url']);
    }

    public function testRedirectUrlsComeFromTheConfiguration(): void
    {
        $transport = new RecordingTransport();
        $client = Factory::client($transport, Factory::config(
            successRedirectUrl: 'https://shop.az/ok',
            errorRedirectUrl: 'https://shop.az/fail',
        ));

        $transport->queue(Response::json(['status' => 'success']));
        $client->pay(100, 'AZN', 'order-1');

        $payload = Factory::sent($transport->lastRequest()->body);

        $this->assertSame('https://shop.az/ok', $payload['success_redirect_url']);
        $this->assertSame('https://shop.az/fail', $payload['error_redirect_url']);
    }

    public function testGetTransactionStatusRenamesTheArgumentToTransaction(): void
    {
        $this->ok();

        $this->client->getTransactionStatus('texxxxxx');

        $request = $this->transport->lastRequest();
        $payload = Factory::sent($request->body);

        $this->assertSame('https://epoint.az/api/1/get-status', $request->uri);
        $this->assertSame('texxxxxx', $payload['transaction']);
        $this->assertFalse(array_key_exists('transaction_id', $payload));
    }

    public function testSaveCardSendsOnlyTheEnvelopeHeaderFields(): void
    {
        $this->ok();

        $this->client->saveCard();

        $request = $this->transport->lastRequest();
        $payload = Factory::sent($request->body);

        $this->assertSame('https://epoint.az/api/1/card-registration', $request->uri);
        $this->assertSame(['public_key', 'language'], array_keys($payload));
    }

    public function testPayWithSavedCardSendsNoRedirectUrls(): void
    {
        $this->ok();

        $this->client->payWithSavedCard(100, 'AZN', 'order-1', 'cexxxxxx');

        $request = $this->transport->lastRequest();
        $payload = Factory::sent($request->body);

        $this->assertSame('https://epoint.az/api/1/execute-pay', $request->uri);
        $this->assertSame('cexxxxxx', $payload['card_id']);

        // Ödəniş server tərəfdə icra olunur — yönləndiriləcək yer yoxdur.
        $this->assertFalse(array_key_exists('success_redirect_url', $payload));
        $this->assertFalse(array_key_exists('error_redirect_url', $payload));
    }

    public function testPayoutAndRefundUseTheEndpointsEPointNamedBackwards(): void
    {
        $this->ok();
        $this->client->payout(100, 'AZN', 'order-1', 'cexxxxxx');

        // `/refund-request` payout-dur, `/reverse` isə refund. İkisini dəyişmək
        // pulu səhv istiqamətdə hərəkət etdirir.
        $this->assertSame('https://epoint.az/api/1/refund-request', $this->transport->lastRequest()->uri);

        $this->ok();
        $this->client->refund('texxxxxx', 'AZN');

        $this->assertSame('https://epoint.az/api/1/reverse', $this->transport->lastRequest()->uri);
    }

    public function testRefundOmitsTheAmountForAFullRefund(): void
    {
        $this->ok();

        $this->client->refund('texxxxxx', 'AZN');

        $payload = Factory::sent($this->transport->lastRequest()->body);

        $this->assertSame('texxxxxx', $payload['transaction']);
        $this->assertNull($payload['amount']);
    }

    public function testRefundCarriesTheAmountForAPartialRefund(): void
    {
        $this->ok();

        $this->client->refund('texxxxxx', 'AZN', 50);

        $payload = Factory::sent($this->transport->lastRequest()->body);

        $this->assertSame('50', $payload['amount']);
    }

    public function testSplitPayRenamesTheUserArgumentToSplitUser(): void
    {
        $this->ok();

        $this->client->splitPay(100, 'AZN', 'order-1', 'epoint-user', 50);

        $request = $this->transport->lastRequest();
        $payload = Factory::sent($request->body);

        $this->assertSame('https://epoint.az/api/1/split-request', $request->uri);
        $this->assertSame('epoint-user', $payload['split_user']);
        $this->assertSame('50', $payload['split_amount']);
        $this->assertFalse(array_key_exists('split_user_id', $payload));
    }

    public function testTheSplitEndpointsAreDistinct(): void
    {
        $this->ok();
        $this->client->splitPayWithSavedCard(100, 'AZN', 'order-1', 'cexxxxxx', 'epoint-user', 50);
        $this->assertSame('https://epoint.az/api/1/split-execute-pay', $this->transport->lastRequest()->uri);

        $this->ok();
        $this->client->splitPayAndSaveCard(100, 'AZN', 'order-1', 'epoint-user', 50);
        $this->assertSame(
            'https://epoint.az/api/1/split-card-registration-with-pay',
            $this->transport->lastRequest()->uri,
        );
    }

    public function testTheCardRegistrationVariantsSendNoOtherAttrKeyAtAll(): void
    {
        // `null` field-lər payload-da qalır, ona görə `PaymentRequest`-i təkrar
        // istifadə etmək məftilə `"other_attr": null` göndərərdi — Python bu
        // endpoint-lər üçün field-i heç elan etmir. Ayrı DTO-lar məhz buna görədir.
        $this->ok();
        $this->client->payAndSaveCard(100, 'AZN', 'order-1', 'Ödəniş');

        $payload = Factory::sent($this->transport->lastRequest()->body);
        $this->assertSame('Ödəniş', $payload['description']);
        $this->assertFalse(array_key_exists('other_attr', $payload));

        $this->ok();
        $this->client->splitPayAndSaveCard(100, 'AZN', 'order-1', 'epoint-user', 50);

        $payload = Factory::sent($this->transport->lastRequest()->body);
        $this->assertSame('epoint-user', $payload['split_user']);
        $this->assertFalse(array_key_exists('other_attr', $payload));
    }

    public function testThePlainPayEndpointsDoSendTheOtherAttrKey(): void
    {
        // Əksi də yoxlanılır: `pay()` və `splitPay()` üçün field mövcuddur, sadəcə
        // dəyəri `null`-dur — yuxarıdaki test "field yoxdur" deyə səhvən keçməsin.
        $this->ok();
        $this->client->pay(100, 'AZN', 'order-1');
        $this->assertTrue(array_key_exists('other_attr', Factory::sent($this->transport->lastRequest()->body)));

        $this->ok();
        $this->client->splitPay(100, 'AZN', 'order-1', 'epoint-user', 50);
        $this->assertTrue(array_key_exists('other_attr', Factory::sent($this->transport->lastRequest()->body)));
    }

    public function testTheAmountIsSentAsAJsonString(): void
    {
        $this->ok();

        $this->client->pay(100, 'AZN', 'order-1');

        $body = $this->transport->lastRequest()->body;
        $this->assertIsArray($body);
        $data = $body['data'];
        $this->assertIsString($data);

        // EPoint məbləği sətir kimi gözləyir; Python da `Decimal`-ı belə serialize edir.
        $this->assertTrue(str_contains((string) base64_decode($data, true), '"amount":"100"'));
    }

    public function testAnIntegerAmountLosesNoTrailingZeroes(): void
    {
        $this->ok();
        $this->client->pay(100, 'AZN', 'o');
        $this->assertSame('100', Factory::sent($this->transport->lastRequest()->body)['amount']);

        $this->ok();
        $this->client->pay('10.50', 'AZN', 'o');
        $this->assertSame('10.50', Factory::sent($this->transport->lastRequest()->body)['amount']);
    }

    public function testAFloatAmountIsNeverWrittenInScientificNotation(): void
    {
        $this->ok();

        $this->client->pay(1.0e20, 'AZN', 'order-1');

        $amount = Factory::sent($this->transport->lastRequest()->body)['amount'];

        $this->assertIsString($amount);
        $this->assertFalse(str_contains(strtolower($amount), 'e'));
        $this->assertSame('100000000000000000000', $amount);
    }

    public function testAFloatAmountDropsBinaryRepresentationNoise(): void
    {
        $this->ok();

        $this->client->pay(0.1 + 0.2, 'AZN', 'order-1');

        // `(string) (0.1 + 0.2)` PHP-də `0.3` verir, lakin `json_encode` `0.30000000000000004`.
        $this->assertSame('0.3', Factory::sent($this->transport->lastRequest()->body)['amount']);
    }

    public function testANonNumericAmountIsRefused(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->pay('sonra ödəyəcəm', 'AZN', 'order-1');
    }

    public function testAScientificNotationStringIsRefused(): void
    {
        // `is_numeric('1e5')` true-dur, EPoint isə bunu qəbul etmir — ona görə
        // yoxlama `is_numeric`-dən sonra ayrıca aparılır.
        $this->expectException(InvalidRequest::class);

        $this->client->pay('1e5', 'AZN', 'order-1');
    }

    public function testANonFiniteAmountIsRefused(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->pay(INF, 'AZN', 'order-1');
    }

    public function testAnEmptyOtherAttrIsSentAsAJsonObject(): void
    {
        $this->ok();

        $this->client->pay(100, 'AZN', 'order-1', otherAttr: []);

        $body = $this->transport->lastRequest()->body;
        $this->assertIsArray($body);
        $data = $body['data'];
        $this->assertIsString($data);

        // PHP-də `[]` JSON-a `[]` kimi yazılır; `other_attr` API tərəfdə obyektdir.
        $json = (string) base64_decode($data, true);
        $this->assertTrue(str_contains($json, '"other_attr":{}'));
        $this->assertFalse(str_contains($json, '"other_attr":[]'));
    }

    public function testTheRequestIsSentAsJson(): void
    {
        $this->ok();

        $this->client->pay(100, 'AZN', 'order-1');

        $headers = $this->transport->lastRequest()->headers;

        $this->assertSame('application/json', $headers['Content-Type']);
    }

    public function testNonAsciiTextIsEscapedTheSameWayPythonEscapesIt(): void
    {
        $this->ok();

        $this->client->pay(100, 'AZN', 'order-1', description: 'Ödəniş');

        $body = $this->transport->lastRequest()->body;
        $this->assertIsArray($body);
        $data = $body['data'];
        $this->assertIsString($data);

        $json = (string) base64_decode($data, true);

        // `json.dumps()` default-da ASCII-dən kənar simvolları escape edir; PHP-nin
        // default-u da eynidir, ona görə `JSON_UNESCAPED_UNICODE` qəsdən verilmir.
        //
        // Gözlənilən escape `Ö` (`Ö`) — sətir `chr(92)` ilə qurulur ki, faylın
        // özündə `Ö` yazılışı olmasın: bəzi redaktor və alətlər onu oxuyanda
        // həqiqi simvola çevirir və test səssizcə mənasını dəyişir.
        $backslash = chr(92);

        $this->assertTrue(str_contains($json, $backslash . 'u00d6'));
        $this->assertTrue(str_contains($json, $backslash . 'u0259'));
        $this->assertTrue(str_contains($json, $backslash . 'u015f'));

        // Xam UTF-8 baytları məftildə görünməməlidir.
        $this->assertStringNotContainsString('Ödəniş', $json);
    }
}
