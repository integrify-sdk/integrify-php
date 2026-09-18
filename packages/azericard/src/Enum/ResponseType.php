<?php

declare(strict_types=1);

namespace Integrify\Azericard\Enum;

/**
 * `finalize()` əməliyyatının növü — bloklanmış məbləğlə nə ediləcəyi.
 */
enum ResponseType: string
{
    /** Bloklanmış məbləği silir. */
    case AcceptPayment = '21';

    /** Ödənişi geri qaytarır. */
    case ReturnPayment = '22';

    /** Bloklanmış məbləği azad edir. */
    case CancelPayment = '24';
}
