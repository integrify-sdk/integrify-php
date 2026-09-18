<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Enum;

/**
 * Sifarişin vəziyyəti.
 *
 * Cavab DTO-larında bu enum **tip kimi istifadə olunmur** — Kapital Bank sabah yeni
 * status əlavə etsə, validasiya sınmamalıdır. DTO xam `string` saxlayır, bu enum isə
 * DTO-nun `status()` metodu vasitəsilə əlçatandır.
 *
 * İzahlar bankın sənədlərindən olduğu kimi (ingiliscə) saxlanılıb.
 */
enum OrderStatus: string
{
    /** Order is being prepared, no transactions have been executed on it yet. */
    case BeingPrepared = 'Preparing';

    /**
     * Order has been cancelled by the consumer (before payment). (Order is cancelled by the
     * merchant.)
     */
    case Cancelled = 'Cancelled';

    /** Order has been rejected by the PSP (before payment). (Order is rejected by the PSP.) */
    case Rejected = 'Rejected';

    /**
     * Consumer has refused to pay for the order (before payment or after unsuccessful payment
     * attempt). (Order is refused by the consumer.)
     */
    case Refused = 'Refused';

    /** Order has expired (before payment). (Timeout occurs when executing the order scenario.) */
    case Expired = 'Expired';

    /** Order has been authorized.(Authorization transaction is executed.) */
    case Authorized = 'Authorized';

    /**
     * Order has been partially paid. (Clearing transaction is executed for the part of the
     * order amount.)
     */
    case PartiallyPaid = 'PartiallyPaid';

    /**
     * Order has been fully paid. (Clearing transaction is executed for the full order amount
     * (or several clearing transactions).)
     */
    case FullyPaid = 'FullyPaid';

    /**
     * Order has been funded (debit transaction has been executed). The status can be assigned
     * only to the order of the DualStep Transfer Order class.
     */
    case Funded = 'Funded';

    /**
     * * AReq and RReq (3DS 2) could not be executed due to rejection by the issuer / error
     * during authentication. * Operation was declined by PMO
     */
    case Declined = 'Declined';

    /** Authorized payment amount under the order is zero. */
    case Voided = 'Voided';

    /** Accounted payment amount and the accounted refund amount under the order are equal. */
    case Refunded = 'Refunded';

    /** Order has been closed (after payment) */
    case Closed = 'Closed';
}
