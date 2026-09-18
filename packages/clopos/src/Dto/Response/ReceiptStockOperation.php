<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Çekin yaratdığı anbar hərəkəti.
 *
 * Çek bağlananda satılan məhsulun resepti anbardan silinir; hər silinmə bir
 * `ReceiptStockOperation` sətridir. `beforeQuantity` və `afterQuantity` hərəkətdən
 * əvvəlki və sonrakı qalığı göstərir.
 */
final readonly class ReceiptStockOperation extends Data
{
    /**
     * @param int|null $id Hərəkətin IDsi.
     * @param int|null $receiptId Hərəkəti yaradan çek.
     * @param int|null $receiptProductId Çekin hansı sətri ilə bağlıdır.
     * @param int|null $productId Qalığı dəyişən məhsul.
     * @param int|null $stockId Anbar qeydinin IDsi.
     * @param int|null $storageId Anbarın IDsi.
     * @param string|null $quantity Silinən (və ya əlavə olunan) miqdar.
     * @param string|null $beforeQuantity Hərəkətdən əvvəlki qalıq.
     * @param string|null $afterQuantity Hərəkətdən sonrakı qalıq.
     * @param string|null $cost Vahidin maya dəyəri.
     * @param string|null $beforeCost Hərəkətdən əvvəlki maya dəyəri.
     * @param string|null $totalCost Hərəkətin ümumi maya dəyəri.
     * @param string|null $operatedAt Hərəkətin vaxtı.
     * @param array<string, mixed>|null $product Məhsulun özü.
     * @param array<string, mixed>|null $stock Anbar qeydinin özü.
     */
    public function __construct(
        public ?int $id = null,
        #[Field(name: 'receipt_id')]
        public ?int $receiptId = null,
        #[Field(name: 'receipt_product_id')]
        public ?int $receiptProductId = null,
        #[Field(name: 'product_id')]
        public ?int $productId = null,
        #[Field(name: 'stock_id')]
        public ?int $stockId = null,
        #[Field(name: 'storage_id')]
        public ?int $storageId = null,
        public ?string $quantity = null,
        #[Field(name: 'before_quantity')]
        public ?string $beforeQuantity = null,
        #[Field(name: 'after_quantity')]
        public ?string $afterQuantity = null,
        public ?string $cost = null,
        #[Field(name: 'before_cost')]
        public ?string $beforeCost = null,
        #[Field(name: 'total_cost')]
        public ?string $totalCost = null,
        #[Field(name: 'operated_at')]
        public ?string $operatedAt = null,
        public ?array $product = null,
        public ?array $stock = null,
    ) {
    }
}
