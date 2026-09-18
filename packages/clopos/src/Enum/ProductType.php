<?php

declare(strict_types=1);

namespace Integrify\Clopos\Enum;

/**
 * Məhsulun növü.
 *
 * Növ məhsulun hansı field-lərinin dolu olacağını təyin edir:
 * `Goods` variantlar (`modifications`), `Dish` resept (`recipe`) və modifikator
 * qrupları, `Ingredient` alış bağlamaları (`packages`), `Timer` isə vaxta görə
 * qiymət cədvəli (`setting`) daşıyır.
 */
enum ProductType: string
{
    /** Hazır mal — variantları ola bilər. */
    case Goods = 'GOODS';

    /** Yemək — resepti və modifikator qrupları ola bilər. */
    case Dish = 'DISH';

    /** Vaxta görə satılan xidmət (məs. bilyard masası). */
    case Timer = 'TIMER';

    /** Yarımfabrikat — özü də reseptdən ibarətdir. */
    case Preparation = 'PREPARATION';

    /** Anbar inqrediyenti. */
    case Ingredient = 'INGREDIENT';

    /** Variant — `Goods` məhsulun ölçü/çeşid variantı. */
    case Modification = 'MODIFICATION';
}
