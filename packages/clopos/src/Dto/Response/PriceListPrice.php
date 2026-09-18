<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Qiymət cədvəlindəki bir sətir — bir məhsulun həmin cədvəldəki qiyməti.
 */
final readonly class PriceListPrice extends Data
{
    /**
     * @param int|null $id Sətrin IDsi.
     * @param int|null $listId Aid olduğu qiymət cədvəli.
     * @param int|null $productId Aid olduğu məhsul.
     * @param string|null $price Qiymət.
     * @param array<string, mixed>|null $product Məhsulun özü (`with[]=product`).
     * @param array<string, mixed>|null $list Cədvəlin özü (`with[]=list`).
     */
    public function __construct(
        public ?int $id = null,
        #[Field(name: 'list_id')]
        public ?int $listId = null,
        #[Field(name: 'product_id')]
        public ?int $productId = null,
        public ?string $price = null,
        public ?array $product = null,
        public ?array $list = null,
    ) {
    }
}
