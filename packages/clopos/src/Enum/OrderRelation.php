<?php

declare(strict_types=1);

namespace Integrify\Clopos\Enum;

/**
 * `getOrder()` sorğusunda əlavə yüklənə bilən əlaqə.
 *
 * Clopos burada **bir** dəyər qəbul edir, siyahı yox.
 */
enum OrderRelation: string
{
    /** Bağlı çekin yalnız IDsi. */
    case ReceiptId = 'receipt:id';

    case ServiceNotificationId = 'service_notification_id';

    case Status = 'status';
}
