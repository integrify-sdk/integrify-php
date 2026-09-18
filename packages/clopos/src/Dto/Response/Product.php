<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Clopos\Enum\ProductType;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Məhsul — Clopos-un ən geniş obyekti.
 *
 * Hansı field-lərin dolu olacağını `type` təyin edir:
 *
 * | `type` | Dolan field-lər |
 * | :--- | :--- |
 * | `GOODS` | `modifications` (variantlar), `modificatorGroups` |
 * | `DISH` | `recipe`, `modificatorGroups` |
 * | `PREPARATION` | `recipe` |
 * | `INGREDIENT` | `packages` |
 * | `TIMER` | `setting` |
 *
 * Əlaqəli obyektlər (`taxes`, `media`, `recipe`, `setting` və s.) yalnız sorğuda
 * `with` verildikdə gəlir — bax `getProduct(1, with: ['recipe', 'taxes'])`.
 *
 * Məbləğlər **sətir** saxlanılır: servis onları JSON ədədi kimi qaytarır, `float`-a
 * çevirmək isə qəpik dəqiqliyini itirərdi.
 */
final readonly class Product extends Data
{
    /**
     * @param int|null $id Məhsulun IDsi.
     * @param string|null $cid POS-dakı unikal identifikator.
     * @param int|null $parentId Əsas məhsul (variant üçün).
     * @param int|null $stationId Hazırlandığı stansiya.
     * @param int|null $categoryId Aid olduğu kateqoriya.
     * @param int|null $unitId Ölçü vahidi.
     * @param int|null $netOutput Xalis çıxım.
     * @param string|null $type Məhsulun növü. Enum üçün `type()`.
     * @param string|null $name Adı.
     * @param string|null $parentName Əsas məhsulun adı.
     * @param Image|null $image Şəkli.
     * @param int|null $position Menyudakı mövqe.
     * @param string|null $description Təsviri.
     * @param string|null $barcode Barkod.
     * @param string|null $govCode Dövlət kodu.
     * @param string|null $cost Maya dəyəri.
     * @param int|null $status `1` aktiv, `0` deaktiv.
     * @param bool|null $hidden Gizlidirmi.
     * @param bool|null $soldByWeight Çəki ilə satılırmı.
     * @param int|null $maxAge Maksimum saxlanma müddəti.
     * @param bool|null $discountable Endirim tətbiq olunurmu.
     * @param bool|null $giftable Hədiyyə edilə bilirmi.
     * @param bool|null $hasModifications Variantları varmı.
     * @param array<string, mixed>|null $meta Əlavə parametrlər.
     * @param TimerSetting|null $setting `TIMER` məhsul üçün qiymət cədvəli.
     * @param list<Variant>|null $modifications `GOODS` məhsulun variantları.
     * @param list<ModifierGroup>|null $modificatorGroups Modifikator qrupları.
     * @param list<Product>|null $recipe `DISH` və `PREPARATION` üçün resept.
     * @param list<PurchasePackage>|null $packages `INGREDIENT` üçün alış bağlamaları.
     * @param list<Product>|null $variants Məhsulun variantları.
     * @param int|null $eMenuId eMenu IDsi.
     * @param int|null $emenuCategoryId eMenu-dakı kateqoriya.
     * @param int|null $emenuPosition eMenu-dakı mövqe.
     * @param bool|null $emenuHidden eMenu-da gizlidirmi.
     * @param int|null $accountingCategoryId Mühasibat kateqoriyası.
     * @param string|null $price Baza qiymət — variantların öz qiyməti ola bilər.
     * @param list<Price>|null $prices Filiala görə qiymətlər.
     * @param string|null $costPrice Maya qiyməti.
     * @param string|null $markupRate Əlavə dəyər dərəcəsi.
     * @param list<Tax>|null $taxes Tətbiq olunan vergilər.
     * @param int|null $cookingTime Hazırlanma müddəti.
     * @param bool|null $ignoreServiceCharge Xidmət haqqından azaddırmı.
     * @param int|null $inventoryBehavior Anbar davranışı.
     * @param int|null $lowStock Az qalıq həddi.
     * @param int|null $unitWeight Vahidin çəkisi (qram).
     * @param string|null $nameSlug Adın slug-ı.
     * @param string|null $fullName Variant məlumatı ilə birlikdə tam ad.
     * @param string|null $grossMargin Ümumi marja.
     * @param string|null $slug Slug.
     * @param string|null $color Rəng.
     * @param list<Venue>|null $venues Satıldığı filiallar.
     * @param array<array-key, mixed>|null $properties Əlavə parametrlər.
     * @param list<Media>|null $media Media faylları.
     * @param array<array-key, mixed>|null $tags Teqlər (sərbəst struktur).
     * @param int|null $totalQuantity Ümumi qalıq.
     * @param string|null $totalCost Ümumi maya dəyəri.
     * @param string|null $averageCost Orta maya dəyəri.
     * @param int|null $openReceiptsCount Açıq çeklərin sayı.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $cid = null,
        #[Field(name: 'parent_id')]
        public ?int $parentId = null,
        #[Field(name: 'station_id')]
        public ?int $stationId = null,
        #[Field(name: 'category_id')]
        public ?int $categoryId = null,
        #[Field(name: 'unit_id')]
        public ?int $unitId = null,
        #[Field(name: 'net_output')]
        public ?int $netOutput = null,
        public ?string $type = null,
        public ?string $name = null,
        #[Field(name: 'parent_name')]
        public ?string $parentName = null,
        public ?Image $image = null,
        public ?int $position = null,
        public ?string $description = null,
        public ?string $barcode = null,
        #[Field(name: 'gov_code')]
        public ?string $govCode = null,
        public ?string $cost = null,
        public ?int $status = null,
        public ?bool $hidden = null,
        #[Field(name: 'sold_by_weight')]
        public ?bool $soldByWeight = null,
        #[Field(name: 'max_age')]
        public ?int $maxAge = null,
        public ?bool $discountable = null,
        public ?bool $giftable = null,
        #[Field(name: 'has_modifications')]
        public ?bool $hasModifications = null,
        public ?array $meta = null,
        public ?TimerSetting $setting = null,
        #[Field(of: Variant::class)]
        public ?array $modifications = null,
        #[Field(name: 'modificator_groups', of: ModifierGroup::class)]
        public ?array $modificatorGroups = null,
        #[Field(of: self::class)]
        public ?array $recipe = null,
        #[Field(of: PurchasePackage::class)]
        public ?array $packages = null,
        #[Field(of: self::class)]
        public ?array $variants = null,
        #[Field(name: 'e_menu_id')]
        public ?int $eMenuId = null,
        #[Field(name: 'emenu_category_id')]
        public ?int $emenuCategoryId = null,
        #[Field(name: 'emenu_position')]
        public ?int $emenuPosition = null,
        #[Field(name: 'emenu_hidden')]
        public ?bool $emenuHidden = null,
        #[Field(name: 'accounting_category_id')]
        public ?int $accountingCategoryId = null,
        public ?string $price = null,
        #[Field(of: Price::class)]
        public ?array $prices = null,
        #[Field(name: 'cost_price')]
        public ?string $costPrice = null,
        #[Field(name: 'markup_rate')]
        public ?string $markupRate = null,
        #[Field(of: Tax::class)]
        public ?array $taxes = null,
        #[Field(name: 'cooking_time')]
        public ?int $cookingTime = null,
        #[Field(name: 'ignore_service_charge')]
        public ?bool $ignoreServiceCharge = null,
        #[Field(name: 'inventory_behavior')]
        public ?int $inventoryBehavior = null,
        #[Field(name: 'low_stock')]
        public ?int $lowStock = null,
        #[Field(name: 'unit_weight')]
        public ?int $unitWeight = null,
        #[Field(name: 'name_slug')]
        public ?string $nameSlug = null,
        #[Field(name: 'full_name')]
        public ?string $fullName = null,
        #[Field(name: 'gross_margin')]
        public ?string $grossMargin = null,
        public ?string $slug = null,
        public ?string $color = null,
        #[Field(of: Venue::class)]
        public ?array $venues = null,
        public ?array $properties = null,
        #[Field(of: Media::class)]
        public ?array $media = null,
        public ?array $tags = null,
        #[Field(name: 'total_quantity')]
        public ?int $totalQuantity = null,
        #[Field(name: 'total_cost')]
        public ?string $totalCost = null,
        #[Field(name: 'average_cost')]
        public ?string $averageCost = null,
        #[Field(name: 'open_receipts_count')]
        public ?int $openReceiptsCount = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }

    /**
     * `type`-ın enum qarşılığı, tanınmayan dəyər üçün `null`.
     */
    public function type(): ?ProductType
    {
        return $this->type === null ? null : ProductType::tryFrom($this->type);
    }
}
