<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Yeni sifarişdəki bir məhsul sətri.
 *
 * `meta` sərbəst strukturdur: POS onu olduğu kimi saxlayır və çekə köçürür. Clopos-un
 * nümunələrində orada məhsulun anlıq surəti və qiyməti olur.
 */
final readonly class NewOrderProduct extends Data
{
    /**
     * @param int $productId Məhsulun IDsi.
     * @param int $count Miqdar.
     * @param array<array-key, mixed>|null $productModificators Seçilmiş modifikatorlar.
     * @param array<string, mixed>|null $meta Sətrin əlavə məlumatları.
     */
    public function __construct(
        #[Field(name: 'product_id')]
        public int $productId,
        public int $count,
        #[Field(name: 'product_modificators')]
        public ?array $productModificators = null,
        public ?array $meta = null,
    ) {
    }
}
