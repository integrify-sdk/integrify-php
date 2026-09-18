<?php

declare(strict_types=1);

namespace Integrify\Azericard\Tests;

use Integrify\Azericard\AzericardClient;
use Integrify\Azericard\Enum\AuthorizationType;
use Integrify\Azericard\Enum\ResponseType;
use Integrify\Azericard\FormPost;
use Integrify\Azericard\Signature;
use Integrify\Environment;
use Integrify\Exception\InvalidRequest;
use Integrify\Exception\MissingConfiguration;
use Integrify\Http\RecordingTransport;
use PHPUnit\Framework\TestCase;

/**
 * Forma qaytaran metodlar — bunlar **HTTP sorğusu atmır**.
 */
final class AzericardFormTest extends TestCase
{
    private RecordingTransport $transport;

    private AzericardClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::client($this->transport);
    }

    private function form(): FormPost
    {
        return $this->client->authorize(
            10.5,
            '944',
            '123456',
            'Test order',
            timestamp: Factory::timestamp(),
            nonce: Factory::nonce(),
        );
    }

    public function testAuthorizeSendsNoHttpRequestAtAll(): void
    {
        $this->form();

        // Azericard-ın kart əməliyyatları server-server deyil: forma brauzerə verilir.
        $this->assertCount(0, $this->transport->requests());
    }

    public function testTheFormTargetsTheMpiHost(): void
    {
        $form = $this->form();

        $this->assertSame('https://testmpi.3dsecure.az/cgi-bin/cgi_link', $form->url);
        $this->assertSame('POST', $form->method);
    }

    public function testTheProductionEnvironmentChangesBothHosts(): void
    {
        $client = Factory::client(null, Factory::config(Environment::Prod));

        $form = $client->authorize(1, '944', '123456', 'D', timestamp: Factory::timestamp(), nonce: Factory::nonce());
        $this->assertSame('https://mpi.3dsecure.az/cgi-bin/cgi_link', $form->url);

        $transfer = $client->startTransfer('Shop', 'SRN001', 1, '944', 'Ali', 'https://s.az');
        $this->assertSame('https://mt.azericard.com/payment/view', $transfer->url);
    }

    public function testTheFieldNamesAreUpperCase(): void
    {
        $fields = $this->form()->fields;

        $this->assertSame('10.5', $fields['AMOUNT']);
        $this->assertSame('944', $fields['CURRENCY']);
        $this->assertSame('123456', $fields['ORDER']);
        $this->assertSame('Test order', $fields['DESC']);
        $this->assertSame('1', $fields['TRTYPE']);
        $this->assertSame(Factory::MERCHANT_ID, $fields['TERMINAL']);
        $this->assertSame('20260918103045', $fields['TIMESTAMP']);
    }

    public function testNullFieldsAreDroppedFromTheForm(): void
    {
        $fields = $this->form()->fields;

        // Python `None` göndərir və httpx onu atır; formada isə hər input görünür,
        // ona görə `null` field-lər `value=""` kimi getməməlidir.
        $this->assertFalse(array_key_exists('COUNTRY', $fields));
        $this->assertFalse(array_key_exists('NAME', $fields));
        $this->assertFalse(array_key_exists('M_INFO', $fields));
    }

    public function testTheSignatureIsOverLengthPrefixedValues(): void
    {
        $fields = $this->form()->fields;

        // `uzunluq + dəyər`: 10.5 → '410.5', '944' → '3944', ...
        $expected = Signature::macSource([
            '10.5',
            '944',
            Factory::MERCHANT_ID,
            '1',
            '20260918103045',
            Factory::nonce(),
            'https://shop.az',
        ]);

        $this->assertSame(
            '410.53944' . '8' . Factory::MERCHANT_ID . '11' . '1420260918103045' . '32' . Factory::nonce()
            . '15https://shop.az',
            $expected,
        );
        $this->assertSame(128 * 4, strlen($fields['P_SIGN']));
    }

    public function testTheSignatureVerifiesAgainstThePublicKey(): void
    {
        $fields = $this->form()->fields;

        $details = openssl_pkey_get_details(Factory::privateKey());
        $this->assertIsArray($details);
        $publicKey = $details['key'];
        $this->assertIsString($publicKey);

        $source = Signature::macSource([
            $fields['AMOUNT'],
            $fields['CURRENCY'],
            $fields['TERMINAL'],
            $fields['TRTYPE'],
            $fields['TIMESTAMP'],
            $fields['NONCE'],
            'https://shop.az',
        ]);

        $this->assertSame(
            1,
            openssl_verify($source, (string) hex2bin($fields['P_SIGN']), $publicKey, OPENSSL_ALGO_SHA256),
        );
    }

    public function testAnEmptyValueBecomesADashInTheMacSource(): void
    {
        // Python-un `if val:` davranışı: boş sətir `-` olur.
        $this->assertSame('-', Signature::macSource(['']));
        // Lakin `'0'` sətri doludur — `trtype` `'0'` (bloklama) ola bilər və onu `-`
        // kimi göndərmək imzanı sındırardı.
        $this->assertSame('10', Signature::macSource(['0']));
    }

    public function testSavingACardAddsTheRegisterAction(): void
    {
        $form = $this->client->authorizeAndSaveCard(
            10.5,
            '944',
            '123456',
            'Test order',
            timestamp: Factory::timestamp(),
            nonce: Factory::nonce(),
        );

        $this->assertSame('https://testmpi.3dsecure.az/token/cgi_link', $form->url);
        $this->assertSame('REGISTER', $form->fields['TOKEN_ACTION']);
    }

    public function testPayingWithASavedCardSendsTheToken(): void
    {
        $token = str_repeat('T', 28);

        $form = $this->client->authorizeWithSavedCard(
            10.5,
            '944',
            '123456',
            'Test order',
            $token,
            timestamp: Factory::timestamp(),
            nonce: Factory::nonce(),
        );

        $this->assertSame('https://testmpi.3dsecure.az/token/cgi_link', $form->url);
        $this->assertSame($token, $form->fields['TOKEN']);
        $this->assertFalse(array_key_exists('TOKEN_ACTION', $form->fields));
    }

    public function testFinalizeSignsADifferentFieldSet(): void
    {
        $form = $this->client->finalize(
            10.5,
            '944',
            '123456',
            str_repeat('1', 12),
            'INT123',
            ResponseType::AcceptPayment,
            Factory::timestamp(),
            Factory::nonce(),
        );

        $fields = $form->fields;

        $this->assertSame('21', $fields['TRTYPE']);
        $this->assertSame('111111111111', $fields['RRN']);
        $this->assertSame('INT123', $fields['INT_REF']);

        $details = openssl_pkey_get_details(Factory::privateKey());
        $this->assertIsArray($details);
        $publicKey = $details['key'];
        $this->assertIsString($publicKey);

        // Bu imza `order, amount, currency, terminal, trtype, rrn, int_ref` üzərindədir —
        // `authorize()`-dakından fərqli sıra və fərqli field-lər.
        $source = Signature::macSource([
            '123456',
            '10.5',
            '944',
            Factory::MERCHANT_ID,
            '21',
            '111111111111',
            'INT123',
        ]);

        $this->assertSame(
            1,
            openssl_verify($source, (string) hex2bin($fields['P_SIGN']), $publicKey, OPENSSL_ALGO_SHA256),
        );
    }

    public function testTheFreezeTypeIsSentAsZero(): void
    {
        $form = $this->client->authorize(
            10.5,
            '944',
            '123456',
            'D',
            AuthorizationType::Freeze,
            Factory::timestamp(),
            Factory::nonce(),
        );

        $this->assertSame('0', $form->fields['TRTYPE']);
    }

    public function testBrowserInfoIsBase64EncodedJson(): void
    {
        $info = ['browserScreenHeight' => '1080', 'browserScreenWidth' => '1920'];

        $form = $this->client->authorize(
            10.5,
            '944',
            '123456',
            'D',
            browserInfo: $info,
            timestamp: Factory::timestamp(),
            nonce: Factory::nonce(),
        );

        $this->assertSame($info, json_decode((string) base64_decode($form->fields['M_INFO'], true), true));
    }

    public function testStartTransferTargetsTheMtHostAndUsesGet(): void
    {
        $form = $this->client->startTransfer('Shop', 'SRN001', 10.5, '944', 'Ali Aliyev', 'https://s.az');

        $this->assertSame('https://testmt.azericard.com/payment/view', $form->url);
        $this->assertSame('GET', $form->method);
        $this->assertSame('Shop', $form->fields['Merchant']);
        $this->assertSame('SRN001', $form->fields['SRN']);
    }

    public function testTheTransferSignatureIsMd5OfTheValuesPlusTheKey(): void
    {
        $form = $this->client->startTransfer('Shop', 'SRN001', 10.5, '944', 'Ali Aliyev', 'https://s.az');

        $this->assertSame(
            Factory::transferSignature(['Shop', 'SRN001', '10.5', '944', 'Ali Aliyev', 'https://s.az']),
            $form->fields['Signature'],
        );
    }

    public function testTheTransferKeyIsNotTheRsaKey(): void
    {
        // İki ayrı açar: MT MD5 açarı kart əməliyyatlarının RSA açarı deyil.
        $client = Factory::client(null, Factory::config(transferKey: null));

        $this->expectException(MissingConfiguration::class);

        $client->startTransfer('Shop', 'SRN001', 1, '944', 'Ali', 'https://s.az');
    }

    public function testACardOperationWithoutMerchantDetailsIsRefused(): void
    {
        $client = Factory::client(null, Factory::config(merchantUrl: null));

        $this->expectException(MissingConfiguration::class);

        $client->authorize(1, '944', '123456', 'D');
    }

    public function testANonNumericAmountIsRefused(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->authorize('sonra', '944', '123456', 'D');
    }

    public function testTheHtmlFormEscapesValues(): void
    {
        $form = new FormPost('https://x.az/pay', 'POST', [
            'ORDER' => '123"><script>alert(1)</script>',
            'DESC' => "Ödəniş & 'iş'",
        ]);

        $html = $form->toHtml();

        // Escape olunmasa `"` atributdan çıxıb HTML/JS injection-a səbəb olardı.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
    }

    public function testTheHtmlFormSubmitsItself(): void
    {
        $html = $this->form()->toHtml();

        $this->assertStringContainsString('document.azericard_form.submit();', $html);
        $this->assertStringContainsString('action="https://testmpi.3dsecure.az/cgi-bin/cgi_link"', $html);
        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringNotContainsString('type="submit"', $html);
    }

    public function testTheSubmitButtonIsOptional(): void
    {
        // JavaScript sönülüdürsə forma özü göndərilmir, düymə yeganə yoldur.
        $this->assertStringContainsString('type="submit"', $this->form()->toHtml(withSubmit: true));
    }

    public function testTheFormIsAlsoAvailableAsAnArray(): void
    {
        $array = $this->form()->toArray();

        $this->assertSame(['url', 'method', 'fields'], array_keys($array));
        $this->assertSame('https://testmpi.3dsecure.az/cgi-bin/cgi_link', $array['url']);
    }
}
