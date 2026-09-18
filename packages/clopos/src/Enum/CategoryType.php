<?php

declare(strict_types=1);

namespace Integrify\Clopos\Enum;

/**
 * Kateqoriyanın növü.
 *
 * Clopos-da kateqoriya yalnız menyu bölməsi deyil: eyni ağac həm satılan məhsulları,
 * həm anbardakı inqrediyentləri, həm də mühasibat maddələrini saxlayır.
 */
enum CategoryType: string
{
    /** Satışa çıxan məhsullar. */
    case Product = 'PRODUCT';

    /** Anbar inqrediyentləri — resept vasitəsilə məhsula daxil olur. */
    case Ingredient = 'INGREDIENT';

    /** Mühasibat maddələri — satılmır, yalnız hesabatda görünür. */
    case Accounting = 'ACCOUNTING';
}
