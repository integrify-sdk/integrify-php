<?php

declare(strict_types=1);

namespace Integrify\Lsim\Enum;

/**
 * Tək SMS API-sinin `errorCode` dəyərləri.
 *
 * Müsbət kodlar SMS-in vəziyyətini, mənfi kodlar sorğunun xətasını bildirir.
 *
 * Cavab DTO-larında bu enum **tip kimi istifadə olunmur** — LSIM sabah yeni kod
 * əlavə etsə, validasiya sınmamalıdır. DTO xam `int` saxlayır, bu enum isə
 * `SmsResult::code()` vasitəsilə əlçatandır.
 */
enum SmsCode: int
{
    case InQueue = 100;
    case Delivered = 101;
    case Undelivered = 102;
    case Expired = 103;
    case Rejected = 104;
    case Cancelled = 105;
    case Error = 106;
    case Unknown = 107;
    case Sent = 108;
    case BlackListed = 109;

    case InvalidKey = -100;
    case TooLongText = -101;
    case WrongNumberFormat = -102;
    case InvalidSenderName = -103;
    case InsufficientBalance = -104;
    case NumberInBlackList = -105;
    case InvalidTransactionId = -106;
    case IpAddressNotAllowed = -107;
    case InvalidHash = -108;
    case NoHost = -109;
    case ReportingLimitExceeded = -110;

    case InternalError = -500;

    /** Mənfi kod sorğunun uğursuz olduğunu bildirir. */
    public function isFailure(): bool
    {
        return $this->value < 0;
    }
}
