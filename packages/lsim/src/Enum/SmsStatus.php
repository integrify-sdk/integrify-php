<?php

declare(strict_types=1);

namespace Integrify\Lsim\Enum;

/**
 * Toplu göndərilmədə hər bir SMS-in statusu.
 */
enum SmsStatus: int
{
    case Expired = 1;
    case Delivered = 2;
    case Undelivered = 3;
    case Sent = 4;
    case SystemError = 5;
    case BlackList = 6;
    case InQueue = 7;
    case Duplicate = 8;
}
