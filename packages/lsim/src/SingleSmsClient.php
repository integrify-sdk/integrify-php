<?php

declare(strict_types=1);

namespace Integrify\Lsim;

use DateTimeInterface;
use Integrify\Client;
use Integrify\Exception\RequestFailed;
use Integrify\Http\HttpTransport;
use Integrify\Http\Transport;
use Integrify\Lsim\Dto\Response\DeliveryReport;
use Integrify\Lsim\Dto\Response\ReportStatus;
use Integrify\Lsim\Dto\Response\SmsPostResult;
use Integrify\Lsim\Dto\Response\SmsResult;

/**
 * Tək SMS göndərilməsi (`https://apps.lsim.az`).
 *
 * Toplu göndərilmə ayrı API-dir, ayrı host-dadır — ona görə ayrı klientdir:
 * [`BulkSmsClient`](BulkSmsClient.php).
 *
 * ```php
 * $client = new SingleSmsClient(LsimConfig::fromEnvironment());
 *
 * $result = $client->sendSms('994501234567', 'Salam!');
 *
 * $result->isSuccessful();  // bool
 * $result->obj;             // transaction id
 * ```
 */
final class SingleSmsClient extends Client
{
    public const BASE_URL = 'https://apps.lsim.az';

    public function __construct(
        private readonly LsimConfig $config,
        ?Transport $transport = null,
        string $baseUrl = self::BASE_URL,
    ) {
        parent::__construct($transport ?? new HttpTransport(), $baseUrl);
    }

    /**
     * SMS göndərir.
     *
     * **GET** `/quicksms/v1/send`
     *
     * @param string $msisdn Nömrə: ölkə kodu + operator kodu + nömrə (`994501234567`).
     * @param string $text Mesajın məzmunu.
     * @param bool $unicode Mesajda `ə`, `ş`, `ü` kimi simvollar varsa `true` verin.
     * @param string|null $sender Göndərən adı; verilməsə konfiqurasiyadakı istifadə olunur.
     *
     * @throws RequestFailed
     */
    public function sendSms(
        string $msisdn,
        string $text,
        bool $unicode = false,
        ?string $sender = null,
    ): SmsResult {
        $sender ??= $this->config->senderName;

        return $this->get(Endpoint::SendSmsGet->value, [
            'msisdn' => $msisdn,
            'text' => $text,
            'login' => $this->config->login,
            'sender' => $sender,
            'unicode' => self::flag($unicode),
            'key' => $this->config->signature($text, $msisdn, $sender),
        ])->to(SmsResult::class);
    }

    /**
     * SMS-i göndərir və ya gələcək bir zamana planlaşdırır.
     *
     * **POST** `/quicksms/v1/smssender`
     *
     * @param DateTimeInterface|null $at Göndərilmə zamanı. `null` — indi.
     *
     * @throws RequestFailed
     */
    public function scheduleSms(
        string $msisdn,
        string $text,
        ?DateTimeInterface $at = null,
        bool $unicode = false,
        ?string $sender = null,
    ): SmsPostResult {
        $sender ??= $this->config->senderName;

        return $this->post(Endpoint::SendSmsPost->value, [
            'msisdn' => $msisdn,
            'text' => $text,
            'login' => $this->config->login,
            'sender' => $sender,
            'unicode' => $unicode,
            'scheduled' => self::timestamp($at),
            'key' => $this->config->signature($text, $msisdn, $sender),
        ])->to(SmsPostResult::class);
    }

    /**
     * Qalan SMS sayını qaytarır — `obj` field-ində.
     *
     * **GET** `/quicksms/v1/balance`
     *
     * @throws RequestFailed
     */
    public function checkBalance(): SmsResult
    {
        return $this->get(Endpoint::Balance->value, [
            'login' => $this->config->login,
            'key' => $this->config->balanceSignature(),
        ])->to(SmsResult::class);
    }

    /**
     * Göndərilmiş SMS-in status kodunu qaytarır.
     *
     * **GET** `/quicksms/v1/report`
     *
     * Bu endpoint JSON deyil, sadəcə bir ədəd qaytarır (məs., `101`), ona görə cavab
     * DTO-ya hidrasiya olunmur — `ReportStatus::fromBody()` xam body-ni oxuyur.
     *
     * Hesabat yalnız son bir həftə üçün əlçatandır.
     *
     * @param int $transactionId Uğurlu göndərilmədə alınan `obj` dəyəri.
     *
     * @throws RequestFailed
     */
    public function checkStatus(int $transactionId): ReportStatus
    {
        $response = $this->get(Endpoint::ReportGet->value, [
            'trans_id' => $transactionId,
            'login' => $this->config->login,
        ]);

        return ReportStatus::fromBody($response->body);
    }

    /**
     * Göndərilmiş SMS-in mətnli çatdırılma hesabatı.
     *
     * **POST** `/quicksms/v1/smsreporter`
     *
     * @param int $transactionId Uğurlu göndərilmədə alınan `obj` dəyəri.
     *
     * @throws RequestFailed
     */
    public function fetchDeliveryReport(int $transactionId): DeliveryReport
    {
        return $this->post(Endpoint::ReportPost->value, [
            'transid' => $transactionId,
            'login' => $this->config->login,
        ])->to(DeliveryReport::class);
    }

    /**
     * Query parametrində bool.
     *
     * `http_build_query()` `true`/`false`-u `1`/`0` kimi yazır, LSIM isə `true`/`false`
     * gözləyir (Python klienti httpx vasitəsilə məhz bunu göndərir). Fərq yalnız
     * GET sorğularına aiddir — POST body-də adi JSON bool gedir.
     */
    private static function flag(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    /**
     * LSIM-in gözlədiyi timestamp formatı, və ya dərhal göndərilmə üçün `NOW`.
     */
    private static function timestamp(?DateTimeInterface $at): string
    {
        return $at?->format('Y-m-d H:i:s') ?? 'NOW';
    }
}
