<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Sifariş sətrinin meta-sı — qiymət və məhsulun anlıq surəti.
 */
final readonly class OrderProductMeta extends Data
{
    /**
     * @param string|null $price Satış qiyməti.
     * @param OrderProductProduct|null $orderProduct Məhsulun surəti.
     */
    public function __construct(
        public ?string $price = null,
        #[Field(name: 'order_product')]
        public ?OrderProductProduct $orderProduct = null,
    ) {
    }
}
