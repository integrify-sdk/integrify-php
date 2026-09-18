<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Sifarişdəki bir məhsul sətri.
 */
final readonly class OrderProduct extends Data
{
    /**
     * @param int|null $productId Məhsulun IDsi.
     * @param int|null $count Miqdar.
     * @param array<array-key, mixed>|null $productModificators Seçilmiş modifikatorlar.
     * @param OrderProductMeta|null $meta Qiymət və məhsulun surəti.
     */
    public function __construct(
        #[Field(name: 'product_id')]
        public ?int $productId = null,
        public ?int $count = null,
        #[Field(name: 'product_modificators')]
        public ?array $productModificators = null,
        public ?OrderProductMeta $meta = null,
    ) {
    }
}
