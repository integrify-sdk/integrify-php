<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Request;

use Integrify\Clopos\Enum\ProductType;

/**
 * `listProducts()` üçün filtr toplusu.
 *
 * Bu, `Data` alt class-ı **deyil**: filtr JSON body-yə yox, query açarlarına çevrilir.
 * Clopos hər filtri `[ad, dəyər]` cütü kimi gözləyir, ona görə məftildə belə görünür:
 *
 * ```
 * filters[giftable][0]=giftable&filters[giftable][1]=1
 * ```
 *
 * Bool field-lər `1`/`0` kimi gedir — Clopos onları sətir `"1"` kimi sənədləşdirib,
 * lakin ədəd də qəbul edir və Python kitabxanası da ədəd göndərir.
 */
final readonly class ProductFilter
{
    /**
     * @param list<ProductType>|null $type Məhsul növləri.
     * @param list<int>|null $categoryId Kateqoriya IDləri.
     * @param list<int>|null $stationId Stansiya IDləri.
     * @param list<int>|null $tags Teq IDləri.
     * @param bool|null $giftable Hədiyyə edilə bilənlər.
     * @param bool|null $discountable Endirim tətbiq olunanlar.
     * @param int|null $inventoryBehavior Anbar davranışı.
     * @param bool|null $haveIngredients Resepti olanlar.
     * @param bool|null $soldByPortion Porsiya ilə satılanlar.
     * @param bool|null $hasVariants Variantı olanlar.
     * @param bool|null $hasModifiers Modifikator qrupu olanlar.
     * @param bool|null $hasBarcode Barkodu olanlar.
     * @param bool|null $hasServiceCharge Xidmət haqqı tətbiq olunanlar.
     */
    public function __construct(
        public ?array $type = null,
        public ?array $categoryId = null,
        public ?array $stationId = null,
        public ?array $tags = null,
        public ?bool $giftable = null,
        public ?bool $discountable = null,
        public ?int $inventoryBehavior = null,
        public ?bool $haveIngredients = null,
        public ?bool $soldByPortion = null,
        public ?bool $hasVariants = null,
        public ?bool $hasModifiers = null,
        public ?bool $hasBarcode = null,
        public ?bool $hasServiceCharge = null,
    ) {
    }

    /**
     * Clopos-un gözlədiyi `ad => [ad, dəyər]` formasına çevirir.
     *
     * Təyin olunmamış (`null`) field-lər tamamilə düşür.
     *
     * @return array<string, array{string, int|list<int>|list<string>}>
     */
    public function toArray(): array
    {
        $filters = [];

        foreach (self::values($this) as $name => $value) {
            $filters[$name] = [$name, $value];
        }

        return $filters;
    }

    /**
     * @return array<string, int|list<int>|list<string>>
     */
    private static function values(self $filter): array
    {
        $values = [];

        if ($filter->type !== null) {
            $values['type'] = array_map(static fn (ProductType $type): string => $type->value, $filter->type);
        }

        if ($filter->categoryId !== null) {
            $values['category_id'] = array_values($filter->categoryId);
        }

        if ($filter->stationId !== null) {
            $values['station_id'] = array_values($filter->stationId);
        }

        if ($filter->tags !== null) {
            $values['tags'] = array_values($filter->tags);
        }

        if ($filter->inventoryBehavior !== null) {
            $values['inventory_behavior'] = $filter->inventoryBehavior;
        }

        // Python-un `BoolInt` validator-u: `int(bool(v))` — yəni `1` və ya `0`.
        foreach ([
            'giftable' => $filter->giftable,
            'discountable' => $filter->discountable,
            'have_ingredients' => $filter->haveIngredients,
            'sold_by_portion' => $filter->soldByPortion,
            'has_variants' => $filter->hasVariants,
            'has_modifiers' => $filter->hasModifiers,
            'has_barcode' => $filter->hasBarcode,
            'has_service_charge' => $filter->hasServiceCharge,
        ] as $name => $flag) {
            if ($flag !== null) {
                $values[$name] = $flag ? 1 : 0;
            }
        }

        return $values;
    }
}
