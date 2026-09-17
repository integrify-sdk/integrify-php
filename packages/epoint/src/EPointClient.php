<?php

declare(strict_types=1);

namespace Integrify\EPoint;

use Integrify\Client;
use Integrify\Dto\Data;
use Integrify\EPoint\Dto\Request\CardRegistrationPaymentRequest;
use Integrify\EPoint\Dto\Request\PaymentRequest;
use Integrify\EPoint\Dto\Request\PayoutRequest;
use Integrify\EPoint\Dto\Request\RefundRequest;
use Integrify\EPoint\Dto\Request\SavedCardPaymentRequest;
use Integrify\EPoint\Dto\Request\SplitCardRegistrationPaymentRequest;
use Integrify\EPoint\Dto\Request\SplitPaymentRequest;
use Integrify\EPoint\Dto\Request\SplitSavedCardPaymentRequest;
use Integrify\EPoint\Dto\Request\TransactionStatusRequest;
use Integrify\EPoint\Dto\Response\MinimalResult;
use Integrify\EPoint\Dto\Response\PaymentResult;
use Integrify\EPoint\Dto\Response\RedirectUrl;
use Integrify\EPoint\Dto\Response\RedirectUrlWithCardId;
use Integrify\EPoint\Dto\Response\SplitPaymentResult;
use Integrify\EPoint\Dto\Response\TransactionStatusResult;
use Integrify\Exception\InvalidRequest;
use Integrify\Exception\RequestFailed;
use Integrify\Http\HttpTransport;
use Integrify\Http\Transport;
use Integrify\Response;
use JsonException;
use stdClass;

/**
 * EPoint ödəniş şlüzü (`https://epoint.az`).
 *
 * ```php
 * $client = new EPointClient(EPointConfig::fromEnvironment());
 *
 * $result = $client->pay(amount: 100, currency: 'AZN', orderId: 'order-1');
 *
 * $result->isSuccessful();   // bool
 * $result->redirectUrl;      // müştərini bura yönləndirin
 * ```
 *
 * **EPoint həmişə HTTP 200 qaytarır**, ödəniş rədd olunanda da. Yəni `RequestFailed`
 * atılmaması "ödəniş alındı" demək deyil — hər cavab DTO-sunda `isSuccessful()` var
 * və ona baxmaq məcburidir. `RequestFailed` yalnız şəbəkə səviyyəsindəki problem
 * (və ya şlüzün 5xx səhifəsi) üçün atılır.
 *
 * Hər sorğunun body-si eyni **zərfdir**: payload JSON-a çevrilib base64-lənir və
 * `EPOINT_PRIVATE_KEY` ilə imzalanır, məftildə isə yalnız `{data, signature}` gedir.
 * Private key heç vaxt göndərilmir.
 */
final class EPointClient extends Client
{
    public const BASE_URL = 'https://epoint.az';

    public function __construct(
        private readonly EPointConfig $config,
        ?Transport $transport = null,
        string $baseUrl = self::BASE_URL,
    ) {
        parent::__construct($transport ?? new HttpTransport(), $baseUrl);
    }

    /**
     * Ödəniş başladır.
     *
     * **POST** `/api/1/request`
     *
     * Cavabdaki `redirectUrl` ödənişin **başlanğıcıdır**: müştəri həmin səhifədə kart
     * məlumatlarını yazır. Nəticə isə callback URL-inizə gəlir və `Callback::decode()`
     * ilə açılır — eyni `orderId` ilə.
     *
     * @param int|float|string $amount Ödəniş məbləği. Pul üçün `string` və ya `int`
     *     tövsiyə olunur; `float`-un dəqiqlik itkisi barədə `amount()`-a baxın.
     * @param string $currency Məzənnə. Mümkün dəyər: `AZN`.
     * @param string $orderId Tətbiqinizdə unikal ID. Maksimum 255 simvol.
     * @param string|null $description Ödənişin təsviri. Maksimum 1000 simvol.
     * @param array<string, mixed>|null $otherAttr Callback-də sizə geri qaytarılan
     *     əlavə dəyərlər.
     *
     * @throws InvalidRequest `$amount` ədədi dəyər deyilsə.
     * @throws RequestFailed
     */
    public function pay(
        int|float|string $amount,
        string $currency,
        string $orderId,
        ?string $description = null,
        ?array $otherAttr = null,
    ): RedirectUrl {
        return $this->dispatch(Endpoint::Pay, new PaymentRequest(
            amount: self::amount($amount),
            currency: $currency,
            orderId: $orderId,
            successRedirectUrl: $this->config->successRedirectUrl,
            errorRedirectUrl: $this->config->errorRedirectUrl,
            description: $description,
            otherAttr: $otherAttr,
        ))->to(RedirectUrl::class);
    }

    /**
     * Tranzaksiyanın statusunu soruşur.
     *
     * **POST** `/api/1/get-status`
     *
     * Diqqət: bu cavabın `isSuccessful()`-u **sorğunun** alındığını bildirir, ödənişin
     * uğurlu olduğunu deyil. Ödəniş üçün `isPaid()`.
     *
     * @param string $transactionId EPoint-in tranzaksiya IDsi. Adətən `te` prefiksi ilə.
     *
     * @throws RequestFailed
     */
    public function getTransactionStatus(string $transactionId): TransactionStatusResult
    {
        return $this->dispatch(Endpoint::GetStatus, new TransactionStatusRequest(
            transaction: $transactionId,
        ))->to(TransactionStatusResult::class);
    }

    /**
     * Ödəniş olmadan kartı yadda saxlayır.
     *
     * **POST** `/api/1/card-registration`
     *
     * Cavabda `cardId` gəlir, lakin o **hələ istifadəyə hazır deyil**: müştəri
     * `redirectUrl`-də kartı uğurla qeyd etməyincə onunla ödəniş etmək mümkün deyil.
     * Təsdiq callback ilə, eyni `cardId` ilə gəlir.
     *
     * @throws RequestFailed
     */
    public function saveCard(): RedirectUrlWithCardId
    {
        // Bu endpoint-in payload-ı boşdur — zərf yalnız `public_key` və `language`
        // daşıyır, ona görə request DTO-su da yoxdur.
        return $this->dispatch(Endpoint::SaveCard, null)->to(RedirectUrlWithCardId::class);
    }

    /**
     * Yadda saxlanılmış kartla ödəniş edir.
     *
     * **POST** `/api/1/execute-pay`
     *
     * Müştəri iştirakı olmadan, server tərəfdə icra olunur — ona görə yönləndirmə
     * URL-i yoxdur, nəticə dərhal cavabda gəlir.
     *
     * @param string $cardId Saxlanılmış kartın IDsi. Adətən `ce` prefiksi ilə başlayır.
     *
     * @throws InvalidRequest
     * @throws RequestFailed
     */
    public function payWithSavedCard(
        int|float|string $amount,
        string $currency,
        string $orderId,
        string $cardId,
    ): PaymentResult {
        return $this->dispatch(Endpoint::PayWithSavedCard, new SavedCardPaymentRequest(
            amount: self::amount($amount),
            currency: $currency,
            orderId: $orderId,
            cardId: $cardId,
        ))->to(PaymentResult::class);
    }

    /**
     * Ödəniş edir və kartı yadda saxlayır.
     *
     * **POST** `/api/1/card-registration-with-pay`
     *
     * `$description` burada **məcburidir** — EPoint bu endpoint üçün onu tələb edir,
     * halbuki `pay()` üçün etmir.
     *
     * @throws InvalidRequest
     * @throws RequestFailed
     */
    public function payAndSaveCard(
        int|float|string $amount,
        string $currency,
        string $orderId,
        string $description,
    ): RedirectUrlWithCardId {
        return $this->dispatch(Endpoint::PayAndSaveCard, new CardRegistrationPaymentRequest(
            amount: self::amount($amount),
            currency: $currency,
            orderId: $orderId,
            description: $description,
            successRedirectUrl: $this->config->successRedirectUrl,
            errorRedirectUrl: $this->config->errorRedirectUrl,
        ))->to(RedirectUrlWithCardId::class);
    }

    /**
     * Saxlanılmış karta pul köçürür (payout).
     *
     * **POST** `/api/1/refund-request`
     *
     * Endpoint-in adına baxmayaraq bu **refund deyil**: pul sizin balansınızdan
     * müştərinin kartına gedir. Ödənişi geri qaytarmaq üçün `refund()`.
     *
     * @throws InvalidRequest
     * @throws RequestFailed
     */
    public function payout(
        int|float|string $amount,
        string $currency,
        string $orderId,
        string $cardId,
        ?string $description = null,
    ): PaymentResult {
        return $this->dispatch(Endpoint::Payout, new PayoutRequest(
            amount: self::amount($amount),
            currency: $currency,
            orderId: $orderId,
            cardId: $cardId,
            description: $description,
        ))->to(PaymentResult::class);
    }

    /**
     * Ödənişi geri qaytarır.
     *
     * **POST** `/api/1/reverse`
     *
     * `$amount` verilməsə tam, verilsə yarımçıq geri qaytarma olur.
     *
     * @param string $transactionId Geri qaytarılacaq tranzaksiyanın IDsi.
     * @param int|float|string|null $amount Geri qaytarılacaq məbləğ, və ya `null` — hamısı.
     *
     * @throws InvalidRequest
     * @throws RequestFailed
     */
    public function refund(
        string $transactionId,
        string $currency,
        int|float|string|null $amount = null,
    ): MinimalResult {
        return $this->dispatch(Endpoint::Refund, new RefundRequest(
            transaction: $transactionId,
            currency: $currency,
            amount: $amount === null ? null : self::amount($amount),
        ))->to(MinimalResult::class);
    }

    /**
     * Bölünmüş ödəniş başladır.
     *
     * **POST** `/api/1/split-request`
     *
     * @param string $splitUserId Ödənişin bölündüyü **EPoint istifadəçisinin** IDsi.
     * @param int|float|string $splitAmount Həmin istifadəçiyə gedən məbləğ.
     * @param array<string, mixed>|null $otherAttr Callback-də geri qaytarılan əlavə dəyərlər.
     *
     * @throws InvalidRequest
     * @throws RequestFailed
     */
    public function splitPay(
        int|float|string $amount,
        string $currency,
        string $orderId,
        string $splitUserId,
        int|float|string $splitAmount,
        ?string $description = null,
        ?array $otherAttr = null,
    ): RedirectUrl {
        return $this->dispatch(Endpoint::SplitPay, new SplitPaymentRequest(
            amount: self::amount($amount),
            currency: $currency,
            orderId: $orderId,
            splitUser: $splitUserId,
            splitAmount: self::amount($splitAmount),
            successRedirectUrl: $this->config->successRedirectUrl,
            errorRedirectUrl: $this->config->errorRedirectUrl,
            description: $description,
            otherAttr: $otherAttr,
        ))->to(RedirectUrl::class);
    }

    /**
     * Saxlanılmış kartla bölünmüş ödəniş edir.
     *
     * **POST** `/api/1/split-execute-pay`
     *
     * @throws InvalidRequest
     * @throws RequestFailed
     */
    public function splitPayWithSavedCard(
        int|float|string $amount,
        string $currency,
        string $orderId,
        string $cardId,
        string $splitUserId,
        int|float|string $splitAmount,
        ?string $description = null,
    ): SplitPaymentResult {
        return $this->dispatch(Endpoint::SplitPayWithSavedCard, new SplitSavedCardPaymentRequest(
            amount: self::amount($amount),
            currency: $currency,
            orderId: $orderId,
            cardId: $cardId,
            splitUser: $splitUserId,
            splitAmount: self::amount($splitAmount),
            description: $description,
        ))->to(SplitPaymentResult::class);
    }

    /**
     * Bölünmüş ödəniş edir və kartı yadda saxlayır.
     *
     * **POST** `/api/1/split-card-registration-with-pay`
     *
     * @throws InvalidRequest
     * @throws RequestFailed
     */
    public function splitPayAndSaveCard(
        int|float|string $amount,
        string $currency,
        string $orderId,
        string $splitUserId,
        int|float|string $splitAmount,
        ?string $description = null,
    ): RedirectUrlWithCardId {
        return $this->dispatch(Endpoint::SplitPayAndSaveCard, new SplitCardRegistrationPaymentRequest(
            amount: self::amount($amount),
            currency: $currency,
            orderId: $orderId,
            splitUser: $splitUserId,
            splitAmount: self::amount($splitAmount),
            successRedirectUrl: $this->config->successRedirectUrl,
            errorRedirectUrl: $this->config->errorRedirectUrl,
            description: $description,
        ))->to(RedirectUrlWithCardId::class);
    }

    /**
     * Sorğunu EPoint-in zərfinə salıb göndərir.
     *
     * Zərf hər endpoint üçün eynidir:
     *
     * 1. payload = `{public_key, language}` + sorğunun field-ləri;
     * 2. `data` = base64(json(payload));
     * 3. `signature` = base64(sha1(private_key + data + private_key));
     * 4. body = `{data, signature}`.
     *
     * @throws InvalidRequest Payload JSON-a çevrilə bilmirsə.
     * @throws RequestFailed
     */
    private function dispatch(Endpoint $endpoint, ?Data $request): Response
    {
        $data = base64_encode($this->encode($this->payload($request)));

        return $this->post($endpoint->value, [
            'data' => $data,
            'signature' => $this->config->signature($data),
        ]);
    }

    /**
     * Zərfin içindəki payload.
     *
     * `null` field-lər **saxlanılır** (`toArray()`, `skipNull` olmadan). Python
     * kitabxanası da `exclude_none` vermir, yəni məftildə `"description": null`
     * gedir. Bunu "təmizləmək" iki SDK-nın göndərdiyi payload-u ayırardı, ona görə
     * qəsdən belədir.
     *
     * @return array<string, mixed>
     */
    private function payload(?Data $request): array
    {
        $fields = $request?->toArray() ?? [];

        $payload = [
            'public_key' => $this->config->publicKey,
            'language' => $this->config->language,
            ...$fields,
        ];

        // PHP-də `[]` həm boş massiv, həm boş obyektdir və `json_encode()` onu `[]`
        // kimi yazır. `other_attr` API tərəfdə obyektdir, ona görə boş halda `{}`
        // göndərilməlidir — `[]` tip xətasına səbəb olur.
        if (($payload['other_attr'] ?? null) === []) {
            $payload['other_attr'] = new stdClass();
        }

        return $payload;
    }

    /**
     * Payload-u JSON-a çevirir.
     *
     * `JSON_UNESCAPED_UNICODE` **qəsdən verilmir**: Python-un `json.dumps()`-u
     * ASCII-dən kənar simvolları `\uXXXX` kimi escape edir, PHP-nin default-u da
     * eynidir — yəni `Ödəniş` hər iki kitabxanada eyni baytlara çevrilir.
     *
     * @param array<string, mixed> $payload
     *
     * @throws InvalidRequest
     */
    private function encode(array $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new InvalidRequest(sprintf(
                'The EPoint payload could not be encoded as JSON: %s.',
                $error->getMessage(),
            ));
        }
    }

    /**
     * Məbləği EPoint-in gözlədiyi sətir formatına salır.
     *
     * EPoint məbləği JSON **sətri** kimi gözləyir (`"100"`, `"10.50"`), ədəd kimi yox.
     *
     * `float` qəbul olunur, lakin pul üçün `string` və ya `int` tövsiyə edilir:
     *
     * - `(string) $float` PHP-nin `precision=14` parametrinə tabedir, yəni `1/3`
     *   üçün `0.33333333333333`, `1e20` üçün isə **`1.0E+20`** verir — EPoint
     *   eksponensial yazılışı məbləğ kimi qəbul etmir. Ona görə çevirmə
     *   `sprintf('%.8F')` ilə aparılır və heç vaxt eksponensial olmur.
     * - `0.1 + 0.2` PHP-də `0.30000000000000004`-dür; `%.8F` onu `0.3`-ə gətirir.
     *   Python kitabxanası eyni girişdə `"0.30000000000000004"` göndərir — fərq
     *   şüurludur, çünki niyyət `0.3`-dür.
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

            // `-0.0` və `0.00000001`-dən kiçik dəyərlər `''` və ya `'-'` verir.
            return $formatted === '' || $formatted === '-' ? '0' : $formatted;
        }

        $trimmed = trim($value);

        if ($trimmed === '' || !is_numeric($trimmed)) {
            throw new InvalidRequest(sprintf('Amount "%s" is not a numeric value.', $value));
        }

        // `is_numeric()` `1e5` və `0x1A` kimi yazılışları da qəbul edir; EPoint etmir.
        if (preg_match('/\A-?(?:\d+(?:\.\d+)?|\.\d+)\z/', $trimmed) !== 1) {
            throw new InvalidRequest(sprintf(
                'Amount "%s" must be written in plain decimal notation, not scientific or hexadecimal.',
                $value,
            ));
        }

        return $trimmed;
    }
}
