<?php

declare(strict_types=1);

namespace Integrify\EPoint\Tests;

use Integrify\EPoint\Exception\SignatureMismatch;
use PHPUnit\Framework\TestCase;

/**
 * Callback-in açılması və imza yoxlaması.
 *
 * Callback URL-i internetdən əlçatandır, yəni bu testlər təhlükəsizlik testləridir:
 * saxta callback qəbul olunmamalıdır.
 */
final class CallbackTest extends TestCase
{
    public function testAValidCallbackDecodesIntoTheDto(): void
    {
        $body = Factory::callbackBody([
            'status' => 'success',
            'code' => '000',
            'order_id' => 'order-1',
            'transaction' => 'texxxxxx',
            'card_mask' => '*******1234',
            'amount' => 100,
        ]);

        $data = Factory::callback()->decode($body);

        $this->assertTrue($data->isSuccessful());
        $this->assertSame('order-1', $data->orderId);
        $this->assertSame('texxxxxx', $data->transaction);
        $this->assertSame('*******1234', $data->cardMask);
        $this->assertSame('100', $data->amount);
        $this->assertSame('Təsdiq edildi', $data->codeMessage());
    }

    public function testTheDocumentedPythonFixtureDecodesIdentically(): void
    {
        // Python-un `test_ok_signature_response` testindəki datanın eynisi:
        // `{"status": "sucess"}` — Python fixture-ində statusda yazı səhvi var,
        // ona görə `isSuccessful()` false olmalıdır, `status` isə xam qalmalıdır.
        $data = Factory::callback()->decode(Factory::callbackBody(['status' => 'sucess']));

        $this->assertSame('sucess', $data->status);
        $this->assertNull($data->status());
        $this->assertFalse($data->isSuccessful());
    }

    public function testAForgedSignatureIsRefused(): void
    {
        $data = base64_encode((string) json_encode(['status' => 'success', 'order_id' => 'order-1']));
        $body = http_build_query(['data' => $data, 'signature' => 'not-the-signature']);

        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decode($body);
    }

    public function testATamperedPayloadIsRefused(): void
    {
        // Hücumçu "uğurlu ödəniş" datası qurur, lakin imzanı düzgün hesablaya bilmir.
        $honest = base64_encode((string) json_encode(['status' => 'error', 'order_id' => 'order-1']));
        $forged = base64_encode((string) json_encode(['status' => 'success', 'order_id' => 'order-1']));

        $body = http_build_query(['data' => $forged, 'signature' => Factory::signature($honest)]);

        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decode($body);
    }

    public function testACallbackSignedWithAnotherKeyIsRefused(): void
    {
        $body = Factory::callbackBody(['status' => 'success']);

        $other = Factory::callback(new \Integrify\EPoint\EPointConfig('i000000001', 'another-private-key'));

        $this->expectException(SignatureMismatch::class);

        $other->decode($body);
    }

    public function testAnEmptyBodyIsRefused(): void
    {
        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decode('');
    }

    public function testAnEmptyDataPairIsRefused(): void
    {
        // `data=&signature=<imza of "">` üçün imza **uyğun gəlir**, çünki boş sətrin
        // imzası hesablana bilir. Yoxlama boş dəyəri ayrıca rədd etməsə, tamam boş
        // bir "ödəniş" DTO-su qaytarılardı.
        $body = http_build_query(['data' => '', 'signature' => Factory::signature('')]);

        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decode($body);
    }

    public function testMissingFieldsAreRefused(): void
    {
        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decode('foo=bar');
    }

    public function testDataThatIsNotBase64IsRefused(): void
    {
        $data = '!!!not base64!!!';
        $body = http_build_query(['data' => $data, 'signature' => Factory::signature($data)]);

        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decode($body);
    }

    public function testDataThatIsNotJsonIsRefused(): void
    {
        $data = base64_encode('this is not json');
        $body = http_build_query(['data' => $data, 'signature' => Factory::signature($data)]);

        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decode($body);
    }

    public function testDataThatDecodesToAScalarIsRefused(): void
    {
        $data = base64_encode('42');
        $body = http_build_query(['data' => $data, 'signature' => Factory::signature($data)]);

        $this->expectException(SignatureMismatch::class);

        Factory::callback()->decode($body);
    }

    public function testVerifyAnswersWithoutDecoding(): void
    {
        $callback = Factory::callback();

        $this->assertTrue($callback->verify(Factory::callbackBody(['status' => 'success'])));
        $this->assertFalse($callback->verify(http_build_query(['data' => 'x', 'signature' => 'y'])));
        $this->assertFalse($callback->verify(''));
    }

    public function testVerifyAcceptsAPayloadItCannotDecode(): void
    {
        // `verify()` yalnız imzanı yoxlayır — datanı açmır. Bu, body-ni queue-ya
        // atıb sonra işləmək üçündür, ona görə keçərli imzalı zibil `true` verir,
        // `decode()` isə sonra onu rədd edir.
        $data = base64_encode('not json');
        $body = http_build_query(['data' => $data, 'signature' => Factory::signature($data)]);

        $this->assertTrue(Factory::callback()->verify($body));
    }
}
