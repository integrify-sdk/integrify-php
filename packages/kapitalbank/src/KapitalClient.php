<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank;

use Integrify\Client;
use Integrify\Dto\Data;
use Integrify\Exception\InvalidRequest;
use Integrify\Exception\RequestFailed;
use Integrify\Http\HttpTransport;
use Integrify\Http\Transport;
use Integrify\Kapitalbank\Dto\Request\CardRegistrationOrderRequest;
use Integrify\Kapitalbank\Dto\Request\ClearingRequest;
use Integrify\Kapitalbank\Dto\Request\FullReverseRequest;
use Integrify\Kapitalbank\Dto\Request\OrderRequest;
use Integrify\Kapitalbank\Dto\Request\PartialReverseRequest;
use Integrify\Kapitalbank\Dto\Request\RefundRequest;
use Integrify\Kapitalbank\Dto\Request\SavedCardPaymentRequest;
use Integrify\Kapitalbank\Dto\Response\CreatedOrder;
use Integrify\Kapitalbank\Dto\Response\DetailedOrderInformation;
use Integrify\Kapitalbank\Dto\Response\LinkedCardToken;
use Integrify\Kapitalbank\Dto\Response\OrderInformation;
use Integrify\Kapitalbank\Dto\Response\TransactionResult;
use Integrify\Kapitalbank\Exception\RequestRejected;
use Integrify\Response;

/**
 * Kapital Bank e-commerce şlüzü.
 *
 * ```php
 * $client = new KapitalClient(KapitalConfig::fromEnvironment());
 *
 * $order = $client->createOrder(amount: 10.5, currency: 'AZN', description: 'Sifariş');
 *
 * $order->id;              // sifariş IDsi
 * $order->redirectUrl();   // müştərini bura yönləndirin
 * ```
 *
 * **Xətalar exception-dur.** EPoint-dən fərqli olaraq Kapital Bank uğursuz sorğuya
 * 400-dən böyük status qaytarır, ona görə `isSuccessful()` kimi metod yoxdur —
 * uğursuzluq `RequestRejected` kimi qalxır və bankın `errorCode`-unu daşıyır.
 *
 * Hər sorğu **zərflə** gedir: `/api/order` payload-ları `{"order": {...}}`,
 * `exec-tran` payload-ları isə `{"tran": {...}}` içindədir; cavab da eyni açardan
 * oxunur.
 */
final class KapitalClient extends Client
{
    /** `/api/order` sorğularının zərf açarı. */
    private const ORDER_KEY = 'order';

    /** `exec-tran` sorğularının zərf açarı. */
    private const TRANSACTION_KEY = 'tran';

    public function __construct(
        private readonly KapitalConfig $config,
        ?Transport $transport = null,
        ?string $baseUrl = null,
    ) {
        parent::__construct($transport ?? new HttpTransport(), $baseUrl ?? $config->baseUrl());
    }

    /**
     * Basic auth + JSON. Parol hər sorğuda gedir, ona görə bu header log-a düşməməlidir.
     *
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => $this->config->authorization(),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Adi ödəniş sifarişi yaradır.
     *
     * **POST** `/api/order`
     *
     * Cavabdakı `redirectUrl()` ödənişin **başlanğıcıdır**: müştəri həmin səhifədə
     * kart məlumatlarını yazır.
     *
     * @param int|float|string $amount Ödəniş məbləği. Pul üçün `string` və ya `int`
     *     tövsiyə olunur; `float`-un dəqiqlik itkisi barədə `amount()`-a baxın.
     * @param string $currency Məzənnə. Mümkün dəyərlər: `AZN`, `USD`.
     * @param string $description Ödənişin təsviri.
     *
     * @throws InvalidRequest `$amount` ədədi dəyər deyilsə.
     * @throws RequestRejected Bank sorğunu rədd etsə.
     * @throws RequestFailed Şəbəkə xətası.
     */
    public function createOrder(
        int|float|string $amount,
        string $currency,
        string $description,
    ): CreatedOrder {
        return $this->order($amount, $currency, $description, typeRid: 'Order_SMS')
            ->to(CreatedOrder::class);
    }

    /**
     * Sifarişin qısa vəziyyətini qaytarır.
     *
     * **GET** `/api/order/{orderId}`
     *
     * @throws RequestRejected Sifariş tapılmasa.
     * @throws RequestFailed
     */
    public function getOrderInformation(int $orderId): OrderInformation
    {
        return $this->unwrap(
            $this->request('GET', Endpoint::GetOrder, $orderId),
            self::ORDER_KEY,
        )->to(OrderInformation::class);
    }

    /**
     * Sifarişin tam vəziyyətini qaytarır — kart, 3-D Secure, cihaz və merchant daxil.
     *
     * **GET** `/api/order/{orderId}`
     *
     * Detal səviyyələri query parametri kimi gedir. Python kitabxanasında bunlar
     * endpoint sətrinin içindədir (`...?&tranDetailLevel=2...`, artıq `&` ilə);
     * burada `$query` parametridir, çünki `Client::uri()` path-də `?` görəndə
     * `InvalidRequest` atır — kodlanmamış istifadəçi dəyəri demək olar həmişə belə
     * görünür.
     *
     * @throws RequestRejected Sifariş tapılmasa.
     * @throws RequestFailed
     */
    public function getDetailedOrderInformation(int $orderId): DetailedOrderInformation
    {
        $response = $this->request('GET', Endpoint::GetOrder, $orderId, query: [
            'tranDetailLevel' => 2,
            'tokenDetailLevel' => 2,
            'orderDetailLevel' => 2,
        ]);

        return $this->unwrap($response, self::ORDER_KEY)->to(DetailedOrderInformation::class);
    }

    /**
     * Ödənişi geri qaytarır.
     *
     * **POST** `/api/order/{orderId}/exec-tran`
     *
     * @throws InvalidRequest
     * @throws RequestRejected
     * @throws RequestFailed
     */
    public function refundOrder(int $orderId, int|float|string $amount): TransactionResult
    {
        return $this->transaction($orderId, new RefundRequest(amount: self::amount($amount)));
    }

    /**
     * Ödəniş olmadan kartı yadda saxlayır.
     *
     * **POST** `/api/order`
     *
     * `Order_DMS` növüdür: məbləğ kartda **bloklanır**, silinmir. Token müştəri
     * ödəniş səhifəsində kartı təsdiqlədikdən sonra
     * `getDetailedOrderInformation()->cardToken()` ilə oxunur.
     *
     * @throws InvalidRequest
     * @throws RequestRejected
     * @throws RequestFailed
     */
    public function saveCard(
        int|float|string $amount,
        string $currency,
        string $description,
    ): CreatedOrder {
        return $this->order(
            $amount,
            $currency,
            $description,
            typeRid: 'Order_DMS',
            capturePurposes: ['Cit', 'Recurring'],
            aut: ['purpose' => 'AddCard'],
        )->to(CreatedOrder::class);
    }

    /**
     * Ödəniş edir və kartı yadda saxlayır.
     *
     * **POST** `/api/order`
     *
     * @throws InvalidRequest
     * @throws RequestRejected
     * @throws RequestFailed
     */
    public function payAndSaveCard(
        int|float|string $amount,
        string $currency,
        string $description,
    ): CreatedOrder {
        return $this->order(
            $amount,
            $currency,
            $description,
            typeRid: 'Order_SMS',
            aut: ['purpose' => 'AddCard'],
        )->to(CreatedOrder::class);
    }

    /**
     * Avtorizasiyanı tam ləğv edir.
     *
     * **POST** `/api/order/{orderId}/exec-tran`
     *
     * Məbləğ qəbul etmir: tam ləğv bloklanmış məbləğin hamısını azad edir. Bir
     * hissəsini ləğv etmək üçün `partialReverseOrder()`.
     *
     * @throws RequestRejected
     * @throws RequestFailed
     */
    public function fullReverseOrder(int $orderId): TransactionResult
    {
        return $this->transaction($orderId, new FullReverseRequest());
    }

    /**
     * Bloklanmış məbləği faktiki silir (clearing).
     *
     * **POST** `/api/order/{orderId}/exec-tran`
     *
     * `Order_DMS` axınının ikinci addımıdır: `saveCard()` məbləği bloklayır, clearing
     * onu hesabdan çıxarır.
     *
     * @throws InvalidRequest
     * @throws RequestRejected
     * @throws RequestFailed
     */
    public function clearingOrder(int $orderId, int|float|string $amount): TransactionResult
    {
        return $this->transaction($orderId, new ClearingRequest(amount: self::amount($amount)));
    }

    /**
     * Avtorizasiyanın bir hissəsini ləğv edir.
     *
     * **POST** `/api/order/{orderId}/exec-tran`
     *
     * @throws InvalidRequest
     * @throws RequestRejected
     * @throws RequestFailed
     */
    public function partialReverseOrder(int $orderId, int|float|string $amount): TransactionResult
    {
        return $this->transaction($orderId, new PartialReverseRequest(amount: self::amount($amount)));
    }

    /**
     * Saxlanılmış kartla ödəniş üçün sifariş yaradır.
     *
     * **POST** `/api/order`
     *
     * `Order_REC` növüdür və yönləndirmə URL-i göndərmir — ödəniş server tərəfdə
     * icra olunur. Tək başına kifayət etmir: ardınca `linkCardToken()` və
     * `processPaymentWithSavedCard()` gəlir. Üçünü birlikdə `payWithSavedCard()` edir.
     *
     * @throws InvalidRequest
     * @throws RequestRejected
     * @throws RequestFailed
     */
    public function orderWithSavedCard(
        int|float|string $amount,
        string $currency,
        string $description,
    ): CreatedOrder {
        return $this->order(
            $amount,
            $currency,
            $description,
            typeRid: 'Order_REC',
            redirectUrl: null,
            capturePurposes: null,
        )->to(CreatedOrder::class);
    }

    /**
     * Saxlanılmış kart tokenini sifarişə bağlayır.
     *
     * **POST** `/api/order/{orderId}/set-src-token`
     *
     * @param string $password Sifarişin **öz** parolu (`CreatedOrder::$password`),
     *     merchant parolu deyil.
     *
     * @throws RequestRejected
     * @throws RequestFailed
     */
    public function linkCardToken(int $token, int $orderId, string $password): LinkedCardToken
    {
        // Bu endpoint-in payload-ı digərlərinə oxşamır: iki ayrı zərf daşıyır, ona
        // görə request DTO-su yoxdur.
        $response = $this->request(
            'POST',
            Endpoint::LinkCardToken,
            $orderId,
            body: [
                'order' => ['initiationEnvKind' => 'Server'],
                'token' => ['storedId' => $token],
            ],
            query: ['password' => $password],
        );

        return $this->unwrap($response, self::ORDER_KEY)->to(LinkedCardToken::class);
    }

    /**
     * Bağlanmış kartla ödənişi icra edir.
     *
     * **POST** `/api/order/{orderId}/exec-tran`
     *
     * @param string $password Sifarişin öz parolu.
     *
     * @throws InvalidRequest
     * @throws RequestRejected
     * @throws RequestFailed
     */
    public function processPaymentWithSavedCard(
        int|float|string $amount,
        int $orderId,
        string $password,
    ): TransactionResult {
        $response = $this->request(
            'POST',
            Endpoint::ExecuteTransaction,
            $orderId,
            body: [self::TRANSACTION_KEY => (new SavedCardPaymentRequest(
                amount: self::amount($amount),
            ))->toArray()],
            query: ['password' => $password],
        );

        return $this->unwrap($response, self::TRANSACTION_KEY)->to(TransactionResult::class);
    }

    /**
     * Saxlanılmış kartdan ödəniş — üç sorğunun birləşməsi.
     *
     * Ardıcıllıq: `orderWithSavedCard()` → `linkCardToken()` →
     * `processPaymentWithSavedCard()`. Aralıq addımın nəticəsi lazımdırsa, metodları
     * ayrıca çağırın.
     *
     * **Bu üç sorğu atomik deyil.** İkinci və ya üçüncü addım uğursuz olarsa,
     * yaradılmış sifariş bankda qalır — exception-ı tutub həmin sifarişi
     * `fullReverseOrder()` ilə ləğv etmək çağıran tərəfin işidir.
     *
     * @param int $token Kartın tokeni (`DetailedOrderInformation::cardToken()`).
     *
     * @throws InvalidRequest
     * @throws RequestRejected
     * @throws RequestFailed
     */
    public function payWithSavedCard(
        int $token,
        int|float|string $amount,
        string $currency,
        string $description,
    ): TransactionResult {
        $order = $this->orderWithSavedCard($amount, $currency, $description);

        $this->linkCardToken($token, $order->id, $order->password);

        return $this->processPaymentWithSavedCard($amount, $order->id, $order->password);
    }

    /**
     * `/api/order` sorğularının ortaq gövdəsi.
     *
     * @param list<string>|null $capturePurposes
     * @param array<string, string>|null $aut
     *
     * @throws InvalidRequest
     * @throws RequestRejected
     * @throws RequestFailed
     */
    private function order(
        int|float|string $amount,
        string $currency,
        string $description,
        string $typeRid,
        ?string $redirectUrl = '',
        ?array $capturePurposes = ['Cit'],
        ?array $aut = null,
    ): Response {
        // `''` "konfiqurasiyadan götür" deməkdir; `null` isə "bu endpoint üçün
        // ümumiyyətlə göndərmə" (`orderWithSavedCard`).
        $hppRedirectUrl = $redirectUrl === '' ? $this->config->redirectUrl : $redirectUrl;

        // Kart qeydiyyatı olan və olmayan sorğuların DTO-ları ayrıdır: `null`
        // field-lər payload-dan atılmadığı üçün ortaq DTO `createOrder()`-a
        // Python-un göndərmədiyi `"aut": null` açarını əlavə edərdi.
        $request = $aut === null
            ? new OrderRequest(
                amount: self::amount($amount),
                currency: $currency,
                description: $description,
                language: $this->config->language,
                hppRedirectUrl: $hppRedirectUrl,
                typeRid: $typeRid,
                hppCofCapturePurposes: $capturePurposes,
            )
            : new CardRegistrationOrderRequest(
                amount: self::amount($amount),
                currency: $currency,
                description: $description,
                aut: $aut,
                language: $this->config->language,
                hppRedirectUrl: $hppRedirectUrl,
                typeRid: $typeRid,
                hppCofCapturePurposes: $capturePurposes,
            );

        return $this->unwrap(
            $this->request('POST', Endpoint::Order, body: [self::ORDER_KEY => $request->toArray()]),
            self::ORDER_KEY,
        );
    }

    /**
     * `exec-tran` sorğularının ortaq gövdəsi.
     *
     * @throws RequestRejected
     * @throws RequestFailed
     */
    private function transaction(int $orderId, Data $request): TransactionResult
    {
        $response = $this->request(
            'POST',
            Endpoint::ExecuteTransaction,
            $orderId,
            body: [self::TRANSACTION_KEY => $request->toArray()],
        );

        return $this->unwrap($response, self::TRANSACTION_KEY)->to(TransactionResult::class);
    }

    /**
     * Sorğunu göndərir və bankın xəta cavabını `RequestRejected`-ə çevirir.
     *
     * @param array<array-key, mixed>|null $body
     * @param array<string, scalar> $query
     *
     * @throws RequestRejected Bank 400-dən böyük status qaytarsa və cavab varsa.
     * @throws RequestFailed Şəbəkə xətası (cavab ümumiyyətlə yoxdur).
     */
    private function request(
        string $method,
        Endpoint $endpoint,
        ?int $orderId = null,
        ?array $body = null,
        array $query = [],
    ): Response {
        $path = $orderId === null
            ? $endpoint->value
            : $this->uri($endpoint->value, ['orderId' => $orderId]);

        try {
            return $this->send($method, $path, body: $body, query: $query);
        } catch (RequestFailed $failure) {
            // Cavab yoxdursa bu şəbəkə xətasıdır, bankın rəddi deyil — olduğu kimi
            // ötürürük ki, iki fərqli problem eyni tipə yığılmasın.
            if ($failure->response === null) {
                throw $failure;
            }

            throw RequestRejected::from($failure);
        }
    }

    /**
     * Cavabın zərfini açır.
     *
     * Bank datanı `{"order": {...}}` və ya `{"tran": {...}}` içində qaytarır. Açar
     * yoxdursa boş cavab qaytarılır — DTO-ların bütün field-ləri nullable olduğu üçün
     * bu, exception əvəzinə "boş nəticə" kimi oxunur.
     */
    private function unwrap(Response $response, string $key): Response
    {
        /** @var mixed $data */
        $data = $response->toArray()[$key] ?? [];

        return new Response(
            status: $response->status,
            headers: $response->headers,
            body: (string) json_encode(is_array($data) ? $data : [], JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Məbləği bankın gözlədiyi sətir formatına salır.
     *
     * Kapital Bank məbləği JSON **sətri** kimi gözləyir (`"10.5"`), ədəd kimi yox —
     * Python kitabxanası da `Decimal`-ı belə serialize edir.
     *
     * `float` qəbul olunur, lakin pul üçün `string` və ya `int` tövsiyə edilir:
     * `(string) $float` PHP-nin `precision=14` parametrinə tabedir və `1e20` üçün
     * `1.0E+20` verir, bank isə eksponensial yazılışı qəbul etmir.
     *
     * (Eyni məntiq `integrify/epoint`-də də var. Üçüncü paket buna ehtiyac duyanda
     * `integrify/core`-a köçürülməlidir — indi köçürmək core-un səthini iki
     * istifadəçi üçün genişləndirmək olardı.)
     *
     * @throws InvalidRequest Dəyər ədəd deyilsə, və ya sonlu deyilsə.
     */
    private static function amount(int|float|string $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new InvalidRequest(sprintf(
                    'Amount must be a finite number, got %s.',
                    var_export($value, true),
                ));
            }

            $formatted = rtrim(rtrim(sprintf('%.8F', $value), '0'), '.');

            return $formatted === '' || $formatted === '-' ? '0' : $formatted;
        }

        $trimmed = trim($value);

        if ($trimmed === '' || !is_numeric($trimmed)) {
            throw new InvalidRequest(sprintf('Amount "%s" is not a numeric value.', $value));
        }

        // `is_numeric()` `1e5` və `0x1A` kimi yazılışları da qəbul edir; bank etmir.
        if (preg_match('/\A-?(?:\d+(?:\.\d+)?|\.\d+)\z/', $trimmed) !== 1) {
            throw new InvalidRequest(sprintf(
                'Amount "%s" must be written in plain decimal notation, not scientific or hexadecimal.',
                $value,
            ));
        }

        return $trimmed;
    }
}
