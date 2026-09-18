<?php

declare(strict_types=1);

namespace Integrify\Clopos\Enum;

/**
 * Endirimin hesablanma qaydası.
 *
 * `Percentage` üçün `discountValue` faizdir, `Fixed` üçün isə məbləğ.
 */
enum DiscountType: int
{
    case None = 0;
    case Percentage = 1;
    case Fixed = 2;
}
