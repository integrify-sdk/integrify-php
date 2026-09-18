<?php

declare(strict_types=1);

namespace Integrify\Azericard\Enum;

/**
 * Pul köçürməsi cavabının `ResponseCode` dəyəri.
 *
 * **Sətir-backed-dir, `int` deyil** — və bu, diqqətlə yoxlanılıb. Python sxemində
 * `class TransferStatusCode(str, Enum)` yazılıb, lakin dəyərlər `0`, `105` kimi ədəd
 * literallarıdır; `str` mixin-i onları `'0'`, `'105'` sətirlərinə çevirir. Yəni məftildə
 * gedən dəyər sətirdir, ədəd deyil.
 */
enum TransferStatusCode: string
{
    /** Uğurla tamamlandı. */
    case Success = '0';

    /**
     * Tranzaksiya "Gözləyən" statusunda deyil — onu rədd etmək və ya təsdiqləmək
     * mümkün deyil.
     */
    case DuplicateTransaction = '105';

    /** Giriş məlumatları imzaya uyğun gəlmir. */
    case SignatureError = '106';

    /** Ödəniş arxa tərəfdə tapılmadı. */
    case TransactionNotFound = '112';

    /** Tranzaksiya artıq təsdiqlənib və tamamlanmasını gözləyir. */
    case TransactionActive = '116';

    /** Yalnız `Success` uğurdur. */
    public function isSuccessful(): bool
    {
        return $this === self::Success;
    }
}
