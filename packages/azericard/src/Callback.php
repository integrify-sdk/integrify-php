<?php

declare(strict_types=1);

namespace Integrify\Azericard;

use Integrify\Azericard\Dto\Response\AuthCallback;
use Integrify\Azericard\Dto\Response\TransferCallback;
use Integrify\Azericard\Dto\Response\TransferResult;
use Integrify\Azericard\Exception\SignatureMismatch;
use Integrify\Exception\MissingConfiguration;
use Integrify\Exception\ValidationFailed;

/**
 * Azericard-ın callback URL-inizə post etdiyi datanın oxunması.
 *
 * İki callback var və **təhlükəsizlik baxımından eyni deyillər**:
 *
 * | Callback | İmza | Yoxlanıla bilirmi |
 * | :--- | :--- | :--- |
 * | Kart əməliyyatı (`decodeAuth()`) | `P_SIGN`, RSA | **xeyr** — Azericard-ın public açarı lazımdır |
 * | Pul köçürməsi (`decodeTransfer()`) | `Signature`, MD5 | **bəli** — paylaşılan açar |
 *
 * ```php
 * $callback = new Callback(AzericardConfig::fromEnvironment());
 *
 * $data = $callback->decodeAuth($_POST);
 *
 * if ($data->isSuccessful()) {
 *     // $data->order ilə sifarişi tapın — sonra getTransactionStatus() ilə təsdiqləyin
 * }
 * ```
 */
final readonly class Callback
{
    public function __construct(
        private AzericardConfig $config,
    ) {
    }

    /**
     * Kart əməliyyatının callback-ini oxuyur.
     *
     * > [!WARNING]
     * > **İmza yoxlanılmır.** `P_SIGN` Azericard-ın açarı ilə imzalanıb; yoxlamaq üçün
     * > onların public açarı lazımdır, sizdə isə yalnız öz private açarınız var. Python
     * > kitabxanası da yoxlamır. Ona görə bu callback-i tək başına "ödəniş oldu" sübutu
     * > saymayın: `getTransactionStatus()` ilə təsdiqləyin — həmin sorğu sizin
     * > imzanızla gedir və cavabı şlüzdən birbaşa gəlir.
     *
     * @param array<string, mixed> $data Post olunmuş field-lər (`$_POST`).
     *
     * @throws ValidationFailed Data DTO-ya uyğun gəlmirsə.
     */
    public function decodeAuth(array $data): AuthCallback
    {
        return AuthCallback::from($data);
    }

    /**
     * Pul köçürməsinin callback-ini oxuyur və **imzasını yoxlayır**.
     *
     * İmza `md5(field-lər + açar)` düsturu ilə hesablanır; uyğunsuzluq
     * `SignatureMismatch` atır.
     *
     * @param array<string, mixed> $data Post olunmuş field-lər.
     *
     * @throws SignatureMismatch İmza uyğun gəlmirsə.
     * @throws MissingConfiguration MT açarı təyin olunmayıbsa.
     * @throws ValidationFailed
     */
    public function decodeTransfer(array $data): TransferCallback
    {
        $callback = TransferCallback::from($data);

        Signature::verify(
            [
                $callback->operationId ?? '',
                $callback->srn ?? '',
                $callback->amount ?? '',
                $callback->currency ?? '',
                $callback->cardStatus ?? '',
                $callback->receiverPan ?? '',
                $callback->status ?? '',
                $callback->timestamp ?? '',
                $callback->responseCode ?? '',
                $callback->message ?? '',
            ],
            $this->config->transferKey(),
            $callback->signature ?? '',
        );

        return $callback;
    }

    /**
     * `confirmTransfer()` / `declineTransfer()` cavabının imzasını yoxlayır.
     *
     * Ayrıca metoddur, DTO-nun içində gizli deyil: imzanın uyğunsuzluğu validasiya
     * xətası kimi deyil, ayrıca və aydın bir exception kimi görünməlidir.
     *
     * Təsdiq və rədd cavablarının imza sahələri **fərqlidir** — təsdiqdə `rrn` və
     * `receiverPan` da iştirak edir. `$confirmed` bunu seçir.
     *
     * @param bool $confirmed `true` — `confirmTransfer()` cavabı, `false` — `declineTransfer()`.
     *
     * @throws SignatureMismatch
     * @throws MissingConfiguration
     */
    public function verifyTransferResult(TransferResult $result, bool $confirmed = true): TransferResult
    {
        $values = $confirmed
            ? [
                $result->operationId ?? '',
                $result->srn ?? '',
                $result->rrn ?? '',
                $result->amount ?? '',
                $result->currency ?? '',
                $result->receiverPan ?? '',
                $result->status ?? '',
                $result->timestamp ?? '',
                $result->responseCode ?? '',
                $result->message ?? '',
            ]
            : [
                $result->operationId ?? '',
                $result->srn ?? '',
                $result->amount ?? '',
                $result->currency ?? '',
                $result->status ?? '',
                $result->timestamp ?? '',
                $result->responseCode ?? '',
                $result->message ?? '',
            ];

        Signature::verify($values, $this->config->transferKey(), $result->signature ?? '');

        return $result;
    }
}
