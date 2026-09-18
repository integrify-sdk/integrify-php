<?php

declare(strict_types=1);

namespace Integrify\Clopos\Enum;

/**
 * `getProduct()` sorğusunda əlavə yüklənə bilən əlaqələr.
 *
 * Verilməsə, `Product`-un uyğun field-ləri `null` qalır.
 */
enum ProductRelation: string
{
    case Taxes = 'taxes';
    case Unit = 'unit';
    case Modifications = 'modifications';
    case ModificatorGroups = 'modificator_groups';
    case Recipe = 'recipe';
    case Packages = 'packages';
    case Media = 'media';
    case Tags = 'tags';
    case Setting = 'setting';
}
