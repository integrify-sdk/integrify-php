<?php

declare(strict_types=1);

namespace Integrify\Lsim\Enum;

/**
 * Toplu SMS tranzaksiyasının `responsecode` dəyərləri.
 *
 * Cavab DTO-larında tip kimi istifadə olunmur (bax: `SmsCode`); xam `int` saxlanılır.
 */
enum BulkCode: int
{
    case Success = 0;
    case InProcessNotReady = 1;
    case Duplicate = 2;

    case BadRequest = 100;
    case OperationTypeEmpty = 101;
    case InvalidOperation = 102;
    case EmptyLogin = 103;
    case EmptyPassword = 104;
    case InvalidAuth = 105;
    case EmptyTitle = 106;
    case InvalidTitle = 107;
    case EmptyTaskId = 108;
    case InvalidTaskId = 109;
    case EmptyControlId = 110;
    case EmptyScheduledDate = 111;
    case InvalidScheduledDate = 112;
    case OldScheduledDate = 113;
    case EmptyIsBulk = 114;
    case InvalidIsBulk = 115;
    case InvalidBulkMessage = 116;
    case InvalidBody = 117;
    case InsufficientBalance = 118;

    /** LSIM dokumentasiyasında yoxdur, lakin praktikada qayıdır. */
    case UnknownError = 235;

    public function isSuccessful(): bool
    {
        return $this === self::Success;
    }
}
