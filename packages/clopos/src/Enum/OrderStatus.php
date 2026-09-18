<?php

declare(strict_types=1);

namespace Integrify\Clopos\Enum;

/**
 * Sifarişin həyat dövrü.
 *
 * `updateOrderStatus()` ilə dəyişdirilir; POS-a ötürülən sifariş `Confirmed`
 * vəziyyətinə keçdikdən sonra ona bir receipt bağlanır.
 */
enum OrderStatus: string
{
    case New = 'NEW';
    case Scheduled = 'SCHEDULED';
    case InProgress = 'IN_PROGRESS';
    case Pending = 'PENDING';
    case Ready = 'READY';
    case PickedUp = 'PICKED_UP';
    case Confirmed = 'CONFIRMED';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
    case Received = 'RECEIVED';
    case Ignore = 'IGNORE';
}
