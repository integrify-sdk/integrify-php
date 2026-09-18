<?php

declare(strict_types=1);

namespace Integrify\Azericard;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Integrify\Azericard\Dto\Response\TransactionStatus;
use Integrify\Azericard\Dto\Response\TransferResult;
use Integrify\Azericard\Enum\AuthorizationType;
use Integrify\Azericard\Enum\ResponseType;
use Integrify\Client;
use Integrify\Exception\InvalidRequest;
use Integrify\Exception\MissingConfiguration;
use Integrify\Exception\RequestFailed;
use Integrify\Http\HttpTransport;
use Integrify\Http\Transport;

/**
 * Azericard — kart ödənişləri (MPI) və pul köçürmələri (MT).
 *
 * **Bu klientin əksər metodu HTTP sorğusu atmır.** Azericard-ın kart əməliyyatları
 * server-server deyil: siz müştərinin brauzerinə bir forma verirsiniz, brauzer onu
 * Azericard-a göndərir, nəticə isə sizin callback URL-inizə gəlir.
 *
 * | Metod | Nə edir |
 * | :--- | :--- |
 * | `authorize()` | forma qaytarır — HTTP yoxdur |
 * | `authorizeAndSaveCard()` | forma qaytarır — HTTP yoxdur |
 * | `authorizeWithSavedCard()` | forma qaytarır — HTTP yoxdur |
 * | `finalize()` | forma qaytarır — HTTP yoxdur |
 * | `startTransfer()` | forma qaytarır — HTTP yoxdur |
 * | `getTransactionStatus()` | **POST** edir |
 * | `confirmTransfer()` | **POST** edir |
 * | `declineTransfer()` | **POST** edir |
 *
 * ```php
 * $client = new AzericardClient(AzericardConfig::fromEnvironment());
 *
 * $form = $client->authorize(amount: 10.5, currency: '944', order: '123456', description: 'Sifariş');
 *
 * echo $form->toHtml();   // brauzer bunu Azericard-a göndərir
 * ```
 *
 * Nəticə `AZERICARD_CALLBACK_URL`-ə post olunur və `Callback::decodeAuth()` ilə oxunur.
 */
final class AzericardClient extends Client
{
    /** Azericard-ın gözlədiyi vaxt formatı: `20260918103045`. */
    public const TIMESTAMP_FORMAT = 'YmdHis';

    public function __construct(
        private readonly AzericardConfig $config,
        ?Transport $transport = null,
    ) {
        // Baza url sorğudan asılıdır (MPI və ya MT), ona görə hər sorğu öz tam
        // ünvanını qurur; burada verilən yalnız `Client`-in tələb etdiyi default-dur.
        parent::__construct($transport ?? new HttpTransport(), $config->baseUrl(System::Mpi));
    }

    /**
     * Azericard-ın **iki** host-u var, ona görə baza yoxlaması genişləndirilir.
     *
     * `Client::assertSameHost()` mütləq url-in baza url ilə eyni host-da olmasını
     * tələb edir — səbəbi `defaultHeaders()`-in API açarı daşıya bilməsidir. Burada
     * header-lərdə sirr yoxdur (Azericard hər şeyi payload-da imzalayır), lakin
     * yoxlama yenə də tam açılmır: yalnız konfiqurasiyanın öz MPI və MT ünvanlarına
     * icazə verilir, yəni səhv bir host yenə də `InvalidRequest` verir.
     *
     * @throws InvalidRequest Host tanınmırsa.
     */
    protected function assertSameHost(string $uri): void
    {
        $host = parse_url($uri, PHP_URL_HOST);

        foreach (System::cases() as $system) {
            $allowed = parse_url($this->config->baseUrl($system), PHP_URL_HOST);

            if (is_string($host) && is_string($allowed) && strcasecmp($host, $allowed) === 0) {
                return;
            }
        }

        throw new InvalidRequest(sprintf(
            'Refusing to send a request to "%s": it is neither the Azericard MPI host (%s) '
            . 'nor the MT host (%s) for the %s environment.',
            $uri,
            $this->config->baseUrl(System::Mpi),
            $this->config->baseUrl(System::Mt),
            $this->config->environment->value,
        ));
    }

    /**
     * Ödəniş üçün forma qurur. **HTTP sorğusu atmır.**
     *
     * @param int|float|string $amount Ödəniş məbləği.
     * @param string $currency 3 simvollu valyuta kodu (AZN üçün `944`).
     * @param string $order Unikal sifariş IDsi, 6–32 rəqəm.
     * @param string $description Ödənişin təsviri, maksimum 50 simvol.
     * @param AuthorizationType $type `Direct` — məbləği sil, `Freeze` — blokla.
     * @param DateTimeInterface|null $timestamp Sorğunun vaxtı; `null` — indi (UTC).
     * @param string|null $nonce Təsadüfi dəyər; `null` — avtomatik.
     * @param array<string, mixed>|null $browserInfo `M_INFO` — brauzer məlumatları.
     *
     * @throws InvalidRequest
     * @throws MissingConfiguration Açar və ya merchant məlumatları yoxdursa.
     */
    public function authorize(
        int|float|string $amount,
        string $currency,
        string $order,
        string $description,
        AuthorizationType $type = AuthorizationType::Direct,
        ?DateTimeInterface $timestamp = null,
        ?string $nonce = null,
        ?array $browserInfo = null,
        ?string $name = null,
        ?string $country = null,
        ?string $merchantGmt = null,
    ): FormPost {
        return $this->authorizationForm(
            Endpoint::Authorization,
            $amount,
            $currency,
            $order,
            $description,
            $type,
            $timestamp,
            $nonce,
            $browserInfo,
            $name,
            $country,
            $merchantGmt,
        );
    }

    /**
     * Ödəniş edir və kartı yadda saxlayır. **HTTP sorğusu atmır.**
     *
     * Payload-a `TOKEN_ACTION=REGISTER` əlavə olunur; token sonra callback-də gəlir.
     *
     * @param array<string, mixed>|null $browserInfo `M_INFO` — brauzer məlumatları.
     *
     * @throws InvalidRequest
     * @throws MissingConfiguration
     */
    public function authorizeAndSaveCard(
        int|float|string $amount,
        string $currency,
        string $order,
        string $description,
        AuthorizationType $type = AuthorizationType::Direct,
        ?DateTimeInterface $timestamp = null,
        ?string $nonce = null,
        ?array $browserInfo = null,
        ?string $name = null,
        ?string $country = null,
        ?string $merchantGmt = null,
    ): FormPost {
        return $this->authorizationForm(
            Endpoint::SaveCard,
            $amount,
            $currency,
            $order,
            $description,
            $type,
            $timestamp,
            $nonce,
            $browserInfo,
            $name,
            $country,
            $merchantGmt,
            extra: ['TOKEN_ACTION' => 'REGISTER'],
        );
    }

    /**
     * Saxlanılmış kartla ödəniş forması. **HTTP sorğusu atmır.**
     *
     * @param string $token Callback-də gələn 28 simvollu kart tokeni.
     * @param array<string, mixed>|null $browserInfo `M_INFO` — brauzer məlumatları.
     *
     * @throws InvalidRequest
     * @throws MissingConfiguration
     */
    public function authorizeWithSavedCard(
        int|float|string $amount,
        string $currency,
        string $order,
        string $description,
        string $token,
        AuthorizationType $type = AuthorizationType::Direct,
        ?DateTimeInterface $timestamp = null,
        ?string $nonce = null,
        ?array $browserInfo = null,
        ?string $name = null,
        ?string $country = null,
        ?string $merchantGmt = null,
    ): FormPost {
        return $this->authorizationForm(
            Endpoint::SaveCard,
            $amount,
            $currency,
            $order,
            $description,
            $type,
            $timestamp,
            $nonce,
            $browserInfo,
            $name,
            $country,
            $merchantGmt,
            extra: ['TOKEN' => $token],
        );
    }

    /**
     * Bloklanmış məbləği tamamlayır, qaytarır və ya azad edir. **HTTP sorğusu atmır.**
     *
     * `authorize()` `Freeze` növü ilə çağırılıbsa, bu addım məcburidir.
     *
     * @param string $rrn Callback-də gələn 12 simvollu RRN.
     * @param string $internalReference Callback-də gələn `INT_REF`.
     * @param ResponseType $type `AcceptPayment` — sil, `ReturnPayment` — geri qaytar,
     *     `CancelPayment` — bloku azad et.
     *
     * @throws InvalidRequest
     * @throws MissingConfiguration
     */
    public function finalize(
        int|float|string $amount,
        string $currency,
        string $order,
        string $rrn,
        string $internalReference,
        ResponseType $type = ResponseType::AcceptPayment,
        ?DateTimeInterface $timestamp = null,
        ?string $nonce = null,
    ): FormPost {
        $fields = [
            'ORDER' => $order,
            'AMOUNT' => self::amount($amount),
            'CURRENCY' => $currency,
            'RRN' => $rrn,
            'INT_REF' => $internalReference,
            'TRTYPE' => $type->value,
            'TERMINAL' => $this->config->merchantId,
            'TIMESTAMP' => self::timestamp($timestamp),
            'NONCE' => $nonce ?? self::nonce(),
        ];

        $fields['P_SIGN'] = Signature::rsa(
            [
                $fields['ORDER'],
                $fields['AMOUNT'],
                $fields['CURRENCY'],
                $fields['TERMINAL'],
                $fields['TRTYPE'],
                $fields['RRN'],
                $fields['INT_REF'],
            ],
            $this->config->privateKey(),
        );

        return new FormPost($this->url(Endpoint::Authorization), 'POST', $fields);
    }

    /**
     * Tranzaksiyanın vəziyyətini soruşur. **Bu metod POST edir.**
     *
     * @param AuthorizationType|ResponseType $originalType Soruşulan əməliyyatın növü.
     *
     * @throws MissingConfiguration
     * @throws RequestFailed
     */
    public function getTransactionStatus(
        AuthorizationType|ResponseType $originalType,
        string $order,
        ?DateTimeInterface $timestamp = null,
        ?string $nonce = null,
    ): TransactionStatus {
        $fields = [
            'ORDER' => $order,
            'TERMINAL' => $this->config->merchantId,
            'TRTYPE' => '90',
            'TIMESTAMP' => self::timestamp($timestamp),
            'NONCE' => $nonce ?? self::nonce(),
            'TRAN_TRTYPE' => $originalType->value,
        ];

        $fields['P_SIGN'] = Signature::rsa(
            [
                $fields['ORDER'],
                $fields['TERMINAL'],
                $fields['TRTYPE'],
                $fields['TIMESTAMP'],
                $fields['NONCE'],
            ],
            $this->config->privateKey(),
        );

        return $this->send('POST', $this->url(Endpoint::Authorization), $fields)
            ->to(TransactionStatus::class);
    }

    /**
     * Pul köçürməsi üçün forma qurur. **HTTP sorğusu atmır.**
     *
     * MT sistemidir, ona görə MD5 açarı istifadə olunur — kart əməliyyatlarının RSA
     * açarı deyil.
     *
     * @param string $merchant Şirkət adı.
     * @param string $srn Sizin unikal əməliyyat nömrəniz.
     * @param string $currency 3 rəqəmli valyuta kodu (AZN üçün `944`).
     * @param string $receiverCredentials Alıcının tam adı.
     * @param string $redirectLink Əməliyyatın sonunda yönləndiriləcək ünvan.
     *
     * @throws InvalidRequest
     * @throws MissingConfiguration
     */
    public function startTransfer(
        string $merchant,
        string $srn,
        int|float|string $amount,
        string $currency,
        string $receiverCredentials,
        string $redirectLink,
    ): FormPost {
        $fields = [
            'Merchant' => $merchant,
            'SRN' => $srn,
            'Amount' => self::amount($amount),
            'Cur' => $currency,
            'ReceiverCredentials' => $receiverCredentials,
            'RedirectLink' => $redirectLink,
        ];

        $fields['Signature'] = Signature::md5(array_values($fields), $this->config->transferKey());

        return new FormPost($this->url(Endpoint::Transfer), 'GET', $fields);
    }

    /**
     * Gözləyən köçürməni təsdiqləyir. **Bu metod POST edir.**
     *
     * @throws InvalidRequest
     * @throws MissingConfiguration
     * @throws RequestFailed
     */
    public function confirmTransfer(
        string $merchant,
        string $srn,
        int|float|string $amount,
        string $currency,
        ?DateTimeInterface $timestamp = null,
    ): TransferResult {
        return $this->transfer(Endpoint::TransferConfirm, $merchant, $srn, $amount, $currency, $timestamp);
    }

    /**
     * Gözləyən köçürməni rədd edir. **Bu metod POST edir.**
     *
     * @throws InvalidRequest
     * @throws MissingConfiguration
     * @throws RequestFailed
     */
    public function declineTransfer(
        string $merchant,
        string $srn,
        int|float|string $amount,
        string $currency,
        ?DateTimeInterface $timestamp = null,
    ): TransferResult {
        return $this->transfer(Endpoint::TransferDecline, $merchant, $srn, $amount, $currency, $timestamp);
    }

    /**
     * Kart əməliyyatı formalarının ortaq gövdəsi.
     *
     * @param array<string, mixed>|null $browserInfo
     * @param array<string, string> $extra
     *
     * @throws InvalidRequest
     * @throws MissingConfiguration
     */
    private function authorizationForm(
        Endpoint $endpoint,
        int|float|string $amount,
        string $currency,
        string $order,
        string $description,
        AuthorizationType $type,
        ?DateTimeInterface $timestamp,
        ?string $nonce,
        ?array $browserInfo,
        ?string $name,
        ?string $country,
        ?string $merchantGmt,
        array $extra = [],
    ): FormPost {
        $merchantUrl = $this->config->required($this->config->merchantUrl, 'AZERICARD_MERCHANT_URL');

        // İmzalanan yeddi dəyər ayrıca dəyişənlərdədir: `$fields` massivində `null`
        // ola bilən field-lər də var (EMAIL, COUNTRY, ...), və imza mənbəyini oradan
        // oxumaq "bu dəyər həmişə doludur" fərziyyəsini gizlədərdi.
        $signedAmount = self::amount($amount);
        $signedType = $type->value;
        $signedTimestamp = self::timestamp($timestamp);
        $signedNonce = $nonce ?? self::nonce();

        $fields = [
            'AMOUNT' => $signedAmount,
            'CURRENCY' => $currency,
            'ORDER' => $order,
            'DESC' => $description,
            'TRTYPE' => $signedType,
            'MERCH_NAME' => $this->config->required($this->config->merchantName, 'AZERICARD_MERCHANT_NAME'),
            'MERCH_URL' => $merchantUrl,
            'TERMINAL' => $this->config->merchantId,
            'EMAIL' => $this->config->merchantEmail,
            'COUNTRY' => $country,
            'MERCH_GMT' => $merchantGmt,
            'BACKREF' => $this->config->required($this->config->callbackUrl, 'AZERICARD_CALLBACK_URL'),
            'TIMESTAMP' => $signedTimestamp,
            'LANG' => $this->config->language,
            'NAME' => $name,
            'M_INFO' => self::browserInfo($browserInfo),
            'NONCE' => $signedNonce,
            ...$extra,
        ];

        $signature = Signature::rsa(
            [
                $signedAmount,
                $currency,
                $this->config->merchantId,
                $signedType,
                $signedTimestamp,
                $signedNonce,
                $merchantUrl,
            ],
            $this->config->privateKey(),
        );

        // `null` field-lər formada boş `value=""` kimi getməməlidir — Python onları
        // `None` kimi serialize edir və httpx atır. Formada isə hər input görünür,
        // ona görə `null`-lar burada təmizlənir.
        $fields = array_filter($fields, static fn (?string $value): bool => $value !== null);
        $fields['P_SIGN'] = $signature;

        return new FormPost($this->url($endpoint), 'POST', $fields);
    }

    /**
     * MT təsdiq/rədd sorğularının ortaq gövdəsi.
     *
     * @throws InvalidRequest
     * @throws MissingConfiguration
     * @throws RequestFailed
     */
    private function transfer(
        Endpoint $endpoint,
        string $merchant,
        string $srn,
        int|float|string $amount,
        string $currency,
        ?DateTimeInterface $timestamp,
    ): TransferResult {
        $fields = [
            'Merchant' => $merchant,
            'SRN' => $srn,
            'Amount' => self::amount($amount),
            'Cur' => $currency,
            'Timestamp' => self::timestamp($timestamp),
        ];

        $fields['Signature'] = Signature::md5(array_values($fields), $this->config->transferKey());

        return $this->send('POST', $this->url($endpoint), $fields)->to(TransferResult::class);
    }

    /**
     * Endpoint-in tam ünvanı — hansı sistemə aid olduğuna görə.
     */
    private function url(Endpoint $endpoint): string
    {
        return $this->config->baseUrl($endpoint->system()) . $endpoint->value;
    }

    /**
     * `M_INFO` base64-lənmiş JSON kimi gedir.
     *
     * @param array<string, mixed>|null $info
     *
     * @throws InvalidRequest
     */
    private static function browserInfo(?array $info): ?string
    {
        if ($info === null || $info === []) {
            return null;
        }

        $json = json_encode($info);

        if ($json === false) {
            throw new InvalidRequest('The browser info (M_INFO) could not be encoded as JSON.');
        }

        return base64_encode($json);
    }

    /**
     * Azericard-ın gözlədiyi vaxt damğası.
     *
     * **UTC-dir.** Azericard sənədləri bu field-i GMT olaraq təyin edir, Python
     * kitabxanası isə `datetime.now()` — yəni serverin **yerli** vaxtını — göndərir.
     * Bakıda bu 4 saat fərq deməkdir və şlüz vaxt fərqinə görə sorğunu rədd edə bilər.
     */
    private static function timestamp(?DateTimeInterface $timestamp): string
    {
        $timestamp ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return DateTimeImmutable::createFromInterface($timestamp)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format(self::TIMESTAMP_FORMAT);
    }

    /**
     * 32 simvollu hex nonce.
     */
    private static function nonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Məbləği Azericard-ın gözlədiyi sətir formatına salır.
     *
     * (Eyni məntiq `integrify/epoint` və `integrify/kapitalbank`-də də var. Artıq üç
     * paketdir — `integrify/core`-a köçürülməlidir.)
     *
     * @throws InvalidRequest
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

        if (preg_match('/\A-?(?:\d+(?:\.\d+)?|\.\d+)\z/', $trimmed) !== 1) {
            throw new InvalidRequest(sprintf(
                'Amount "%s" must be written in plain decimal notation, not scientific or hexadecimal.',
                $value,
            ));
        }

        return $trimmed;
    }
}
