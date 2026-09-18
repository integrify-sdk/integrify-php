<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini;

use DateTimeInterface;
use Integrify\Client;
use Integrify\Dto\Data;
use Integrify\Exception\InvalidRequest;
use Integrify\Exception\RequestFailed;
use Integrify\Http\HttpTransport;
use Integrify\Http\Transport;
use Integrify\PostaGuvercini\Dto\Request\CreditBalanceRequest;
use Integrify\PostaGuvercini\Dto\Request\SendMultipleRequest;
use Integrify\PostaGuvercini\Dto\Request\SendSingleRequest;
use Integrify\PostaGuvercini\Dto\Request\SmsMessage;
use Integrify\PostaGuvercini\Dto\Request\StatusRequest;
use Integrify\PostaGuvercini\Dto\Response\BalanceResult;
use Integrify\PostaGuvercini\Dto\Response\SendResult;
use Integrify\PostaGuvercini\Dto\Response\StatusResult;
use Integrify\PostaGuvercini\Enum\Channel;
use Integrify\Response;

/**
 * Posta Güvercini SMS servisi (`https://www.poctgoyercini.com`).
 *
 * ```php
 * $client = new PostaGuverciniClient(PostaGuverciniConfig::fromEnvironment());
 *
 * $result = $client->sendSms('Salam!', ['994501234567']);
 *
 * $result->isSuccessful();   // bool
 * $result->messageIds();     // status sorğusu üçün
 * ```
 *
 * **Servis uğursuz sorğuya da HTTP 200 qaytarır** — nəticə body-dəki `StatusCode`
 * field-indədir. Yəni `RequestFailed` atılmaması "göndərildi" demək deyil; hər cavab
 * DTO-sunda `isSuccessful()` var və ona baxmaq məcburidir.
 *
 * Bütün sorğular `POST`-dur və hamısı hesabın adını və **parolunu payload-da**
 * daşıyır — bax: [`PostaGuverciniConfig`](PostaGuverciniConfig.php).
 */
final class PostaGuverciniClient extends Client
{
    public const BASE_URL = 'https://www.poctgoyercini.com';

    /**
     * Servisin gözlədiyi tarix formatı: `20260918 10:30`.
     *
     * Ayırıcı yoxdur və saniyə də yoxdur. (Python kitabxanasının `types.py` faylında
     * bu format "YYYY-mm-DD HH:MM:SS" kimi sənədləşdirilib, lakin kod `%Y%m%d %H:%M`
     * istifadə edir — sənəd səhvdir, kod düzdür.)
     */
    public const DATE_FORMAT = 'Ymd H:i';

    public function __construct(
        private readonly PostaGuverciniConfig $config,
        ?Transport $transport = null,
        string $baseUrl = self::BASE_URL,
    ) {
        parent::__construct($transport ?? new HttpTransport(), $baseUrl);
    }

    /**
     * Bir mətni bir və ya bir neçə nömrəyə göndərir.
     *
     * **POST** `/api_json/v1/Sms/Send_1_N`
     *
     * @param string $message Göndəriləcək mətn.
     * @param list<string> $receivers Alıcı nömrələri (`994501234567`).
     * @param DateTimeInterface|string|null $sendDate Göndərilmə vaxtı; `null` — indi.
     *     Sətir veriləcəksə formatı `YYYYMMDD HH:MM` olmalıdır.
     * @param DateTimeInterface|string|null $expireDate Etibarlılıq müddəti.
     * @param Channel $channel Kanal; default `OTP`.
     * @param string|null $originator Göndərən adı; verilməsə konfiqurasiyadakı işlənir.
     *
     * @throws InvalidRequest Alıcı siyahısı boşdursa, və ya tarix formatı yanlışdırsa.
     * @throws RequestFailed
     */
    public function sendSms(
        string $message,
        array $receivers,
        DateTimeInterface|string|null $sendDate = null,
        DateTimeInterface|string|null $expireDate = null,
        Channel $channel = Channel::Otp,
        ?string $originator = null,
    ): SendResult {
        if ($receivers === []) {
            // Servis bunu `EmptyReceiverList` (1060) ilə rədd edir; şəbəkəyə çıxmadan
            // dayandırmaq daha aydın bir xətadır.
            throw new InvalidRequest('sendSms() needs at least one receiver.');
        }

        return $this->dispatch(Endpoint::SendSingle, new SendSingleRequest(
            message: $message,
            receivers: array_values($receivers),
            sendDate: self::timestamp($sendDate),
            expireDate: self::timestamp($expireDate),
            channel: $channel->value,
            originator: $originator ?? $this->config->originator,
            username: $this->config->username,
            password: $this->config->password,
        ))->to(SendResult::class);
    }

    /**
     * Hər nömrəyə **öz** mətnini göndərir.
     *
     * **POST** `/api_json/v1/Sms/Send_N_N`
     *
     * ```php
     * $client->sendMessages([
     *     new SmsMessage('994501234567', 'Salam, Əli'),
     *     new SmsMessage('994551234567', 'Salam, Aysel'),
     * ]);
     * ```
     *
     * Nömrə və mətn bir DTO-da saxlanılır, iki paralel siyahı kimi yox: fərqli
     * uzunluqlu siyahılar səssizcə qısalır və bir mesaj heç kimə getmir.
     *
     * @param list<SmsMessage> $messages Nömrə–mətn cütləri.
     *
     * @throws InvalidRequest Siyahı boşdursa, və ya tarix formatı yanlışdırsa.
     * @throws RequestFailed
     */
    public function sendMessages(
        array $messages,
        DateTimeInterface|string|null $sendDate = null,
        DateTimeInterface|string|null $expireDate = null,
        Channel $channel = Channel::Otp,
        ?string $originator = null,
    ): SendResult {
        if ($messages === []) {
            throw new InvalidRequest('sendMessages() needs at least one message.');
        }

        return $this->dispatch(Endpoint::SendMultiple, new SendMultipleRequest(
            messages: array_values($messages),
            sendDate: self::timestamp($sendDate),
            expireDate: self::timestamp($expireDate),
            channel: $channel->value,
            originator: $originator ?? $this->config->originator,
            username: $this->config->username,
            password: $this->config->password,
        ))->to(SendResult::class);
    }

    /**
     * Göndərilmiş mesajların çatdırılma vəziyyətini soruşur.
     *
     * **POST** `/api_json/v1/Sms/Status`
     *
     * @param list<string> $messageIds Göndərmə cavabındakı `messageIds()` dəyərləri.
     *
     * @throws InvalidRequest Siyahı boşdursa.
     * @throws RequestFailed
     */
    public function getStatus(array $messageIds): StatusResult
    {
        if ($messageIds === []) {
            throw new InvalidRequest('getStatus() needs at least one message id.');
        }

        return $this->dispatch(Endpoint::Status, new StatusRequest(
            messageIds: array_values($messageIds),
            username: $this->config->username,
            password: $this->config->password,
        ))->to(StatusResult::class);
    }

    /**
     * Hesabdakı qalan krediti qaytarır.
     *
     * **POST** `/api_json/v1/Sms/CreditBalance`
     *
     * @throws RequestFailed
     */
    public function checkBalance(): BalanceResult
    {
        return $this->dispatch(Endpoint::CreditBalance, new CreditBalanceRequest(
            username: $this->config->username,
            password: $this->config->password,
        ))->to(BalanceResult::class);
    }

    /**
     * Sorğunu göndərir.
     *
     * `null` field-lər payload-da **saxlanılır**: Python kitabxanası da `exclude_none`
     * vermir, yəni məftildə `"SendDate": null` gedir.
     *
     * @throws RequestFailed
     */
    private function dispatch(Endpoint $endpoint, Data $request): Response
    {
        return $this->post($endpoint->value, $request->toArray());
    }

    /**
     * Tarixi servisin gözlədiyi formata salır.
     *
     * Sətir verilibsə format yoxlanılır və uyğun gəlmirsə `InvalidRequest` atılır.
     * Python kitabxanası belə halda səssizcə `None` qaytarır — yəni yazılış səhvi olan
     * bir tarix "indi göndər"ə çevrilir və planlaşdırılmış SMS dərhal gedir. Sonra
     * pydantic `None`-u `str | datetime` ilə uzlaşdıra bilmir və xəta mesajı əsl
     * səbəbi (format) heç yerdə göstərmir.
     *
     * @throws InvalidRequest
     */
    private static function timestamp(DateTimeInterface|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(self::DATE_FORMAT);
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/\A\d{8} \d{2}:\d{2}\z/', $trimmed) !== 1) {
            throw new InvalidRequest(sprintf(
                'Date "%s" is not in the "YYYYMMDD HH:MM" format Posta Guvercini expects '
                . '(e.g. "20260918 10:30"). Pass a DateTimeInterface to avoid formatting it by hand.',
                $value,
            ));
        }

        return $trimmed;
    }
}
