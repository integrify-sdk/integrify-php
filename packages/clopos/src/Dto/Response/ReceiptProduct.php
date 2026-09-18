<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Clopos\Enum\DiscountType;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Çekin bir sətri — satılmış məhsul, qiyməti və endirimləri ilə.
 *
 * Python-da bu iki class-dır (`ReceiptProductIn` və onu genişləndirən
 * `ReceiptProduct`). PHP-də `readonly` class-ı yalnız `readonly` class genişləndirə
 * bilər və promoted property-ni alt class-da yenidən elan etmək olmur, ona görə
 * burada tək, düz class-dır.
 */
final readonly class ReceiptProduct extends Data
{
    /**
     * @param int|null $id Sətrin IDsi.
     * @param string|null $cid Sətrin POS identifikatoru.
     * @param int|null $productId Məhsulun IDsi.
     * @param array<string, mixed>|null $meta Sətrin əlavə məlumatları.
     * @param int|null $count Miqdar.
     * @param string|null $portionSize Porsiya ölçüsü.
     * @param string|null $total Sətrin ümumi məbləği.
     * @param string|null $price Vahidin qiyməti.
     * @param string|null $cost Maya dəyəri.
     * @param bool|null $isGift Hədiyyədirmi.
     * @param int|null $receiptId Aid olduğu çek.
     * @param string|null $productHash Məhsul + modifikator kombinasiyasının hash-i.
     * @param int|null $preprintCount Ön çap sayı.
     * @param int|null $stationPrintedCount Stansiyada çap sayı.
     * @param int|null $stationAbortedCount Stansiyada ləğv sayı.
     * @param int|null $sellerId Satıcının IDsi.
     * @param string|null $loyaltyType Loyallıq növü.
     * @param string|null $loyaltyValue Loyallıq dəyəri.
     * @param string|null $discountRate Endirim dərəcəsi.
     * @param string|null $discountValue Endirim dəyəri.
     * @param int|null $discountType Endirim növü. Enum üçün `discountType()`.
     * @param string|null $totalDiscount Ümumi endirim.
     * @param string|null $subtotal Endirimdən əvvəlki məbləğ.
     * @param string|null $receiptDiscount Çek səviyyəsindəki endirimin payı.
     * @param array<array-key, mixed>|null $receiptProductModificators Seçilmiş modifikatorlar.
     * @param array<array-key, mixed>|null $taxes Sətrə tətbiq olunan vergilər.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $terminalUpdatedAt Terminaldakı son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $cid = null,
        #[Field(name: 'product_id')]
        public ?int $productId = null,
        public ?array $meta = null,
        public ?int $count = null,
        #[Field(name: 'portion_size')]
        public ?string $portionSize = null,
        public ?string $total = null,
        public ?string $price = null,
        public ?string $cost = null,
        #[Field(name: 'is_gift')]
        public ?bool $isGift = null,
        #[Field(name: 'receipt_id')]
        public ?int $receiptId = null,
        #[Field(name: 'product_hash')]
        public ?string $productHash = null,
        #[Field(name: 'preprint_count')]
        public ?int $preprintCount = null,
        #[Field(name: 'station_printed_count')]
        public ?int $stationPrintedCount = null,
        #[Field(name: 'station_aborted_count')]
        public ?int $stationAbortedCount = null,
        #[Field(name: 'seller_id')]
        public ?int $sellerId = null,
        #[Field(name: 'loyalty_type')]
        public ?string $loyaltyType = null,
        #[Field(name: 'loyalty_value')]
        public ?string $loyaltyValue = null,
        #[Field(name: 'discount_rate')]
        public ?string $discountRate = null,
        #[Field(name: 'discount_value')]
        public ?string $discountValue = null,
        #[Field(name: 'discount_type')]
        public ?int $discountType = null,
        #[Field(name: 'total_discount')]
        public ?string $totalDiscount = null,
        public ?string $subtotal = null,
        #[Field(name: 'receipt_discount')]
        public ?string $receiptDiscount = null,
        #[Field(name: 'receipt_product_modificators')]
        public ?array $receiptProductModificators = null,
        public ?array $taxes = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'terminal_updated_at')]
        public ?string $terminalUpdatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }

    /**
     * `discountType`-ın enum qarşılığı, tanınmayan dəyər üçün `null`.
     */
    public function discountType(): ?DiscountType
    {
        return $this->discountType === null ? null : DiscountType::tryFrom($this->discountType);
    }
}
