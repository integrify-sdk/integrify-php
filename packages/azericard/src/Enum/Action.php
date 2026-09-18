<?php

declare(strict_types=1);

namespace Integrify\Azericard\Enum;

/**
 * E-Gateway-in fəaliyyət kodu — callback-də və status cavabında gəlir.
 *
 * Cavab DTO-larında tip kimi istifadə olunmur: Azericard sabah yeni kod əlavə etsə,
 * validasiya sınmamalıdır.
 */
enum Action: int
{
    /** Tranzaksiya uğurla tamamlandı. */
    case Success = 0;

    /** Duplikat əməliyyat aşkar edildi. */
    case Duplicate = 1;

    /** Tranzaksiya rədd edildi. */
    case Cancelled = 2;

    /** Tranzaksiya emal xətası. */
    case ProcessingError = 3;

    /** İmtina edilmiş əməliyyatın təkrarlanması. */
    case RepeatOfCancelled = 6;

    /** Doğrulama xətası ilə əməliyyatın təkrarlanması. */
    case RepeatOfUnapproved = 7;

    /** Cavab verilmədən dayandırılmış əməliyyatın təkrarlanması. */
    case RepeatOfUnresponded = 8;

    /** Yalnız `Success` uğurdur. */
    public function isSuccessful(): bool
    {
        return $this === self::Success;
    }
}
