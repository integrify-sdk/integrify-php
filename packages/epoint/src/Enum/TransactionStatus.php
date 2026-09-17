<?php

declare(strict_types=1);

namespace Integrify\EPoint\Enum;

/**
 * Ödəniş sorğularının `status` dəyərləri.
 *
 * Cavab DTO-larında bu enum **tip kimi istifadə olunmur** — EPoint sabah yeni
 * status əlavə etsə, validasiya sınmamalıdır. DTO xam `string` saxlayır, bu enum
 * isə DTO-nun `status()` metodu vasitəsilə əlçatandır.
 */
enum TransactionStatus: string
{
    case Success = 'success';
    case Error = 'error';
    case ServerError = 'server_error';
    case Failed = 'failed';

    /**
     * Xam dəyəri enum-a çevirir; tanınmasa `null`.
     *
     * DTO-ların `status()` metodları bunu çağırır, ona görə "tanınmayan status"
     * məntiqinin bir yerdə olması vacibdir.
     */
    public static function parse(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    /**
     * Ödəniş sorğusu üçün: yalnız `success` uğurdur.
     *
     * `error` və `failed` uğursuzdur. Diqqət: `getTransactionStatus()` bu qaydadan
     * istisnadır — bax [`TransactionStatusExtended::requestSucceeded()`](TransactionStatusExtended.php).
     */
    public static function succeeded(?string $value): bool
    {
        return $value === self::Success->value;
    }
}
