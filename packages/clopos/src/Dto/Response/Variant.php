<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Clopos\Enum\ProductType;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `Goods` məhsulun variantı — məs. "0.5 L".
 *
 * Variant öz qiyməti və barkodu olan ayrıca sətirdir; Clopos-da onun `type`-ı
 * həmişə `MODIFICATION`-dur.
 */
final readonly class Variant extends Data
{
    /**
     * @param int|null $id Variantın IDsi.
     * @param int|null $parentId Əsas məhsulun IDsi.
     * @param string|null $type Növ — həmişə `MODIFICATION`. Enum üçün `type()`.
     * @param string|null $name Variantın adı.
     * @param string|null $price Satış qiyməti.
     * @param string|null $costPrice Maya dəyəri.
     * @param string|null $barcode Barkod.
     * @param string|null $fullName Tam ad (əsas məhsulla birlikdə).
     * @param int|null $status `1` aktiv, `0` deaktiv.
     */
    public function __construct(
        public ?int $id = null,
        #[Field(name: 'parent_id')]
        public ?int $parentId = null,
        public ?string $type = null,
        public ?string $name = null,
        public ?string $price = null,
        #[Field(name: 'cost_price')]
        public ?string $costPrice = null,
        public ?string $barcode = null,
        #[Field(name: 'full_name')]
        public ?string $fullName = null,
        public ?int $status = null,
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
