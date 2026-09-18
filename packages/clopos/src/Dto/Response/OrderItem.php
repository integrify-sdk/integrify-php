<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Sifarişin sətri — POS tərəfindən hesablanmış miqdar və məbləğ.
 */
final readonly class OrderItem extends Data
{
    /**
     * @param int|null $id Sətrin IDsi.
     * @param int|null $productId Məhsulun IDsi.
     * @param int|null $quantity Miqdar.
     * @param string|null $unitPrice Vahidin qiyməti.
     * @param string|null $totalPrice Sətrin ümumi məbləği.
     */
    public function __construct(
        public ?int $id = null,
        #[Field(name: 'product_id')]
        public ?int $productId = null,
        public ?int $quantity = null,
        #[Field(name: 'unit_price')]
        public ?string $unitPrice = null,
        #[Field(name: 'total_price')]
        public ?string $totalPrice = null,
    ) {
    }
}
