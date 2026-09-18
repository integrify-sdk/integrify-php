<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Sifariş sətrinin meta-sındakı məhsul surəti.
 *
 * > Servis `product` field-ini ya obyekt, ya da **boş massiv** kimi qaytarır (Laravel
 * > yüklənməmiş əlaqəni `[]` yazır). Boş massiv burada bütün field-ləri `null` olan
 * > `Product`-a çevrilir — itən məlumat yoxdur, çünki `[]`-də məlumat yoxdur.
 */
final readonly class OrderProductProduct extends Data
{
    /**
     * @param Product|null $product Məhsulun özü.
     * @param int|null $count Miqdar.
     * @param string|null $status Sətrin vəziyyəti.
     * @param array<array-key, mixed>|null $productModificators Seçilmiş modifikatorlar.
     * @param string|null $productHash Məhsul + modifikator kombinasiyasının hash-i.
     */
    public function __construct(
        public ?Product $product = null,
        public ?int $count = null,
        public ?string $status = null,
        #[Field(name: 'product_modificators')]
        public ?array $productModificators = null,
        #[Field(name: 'product_hash')]
        public ?string $productHash = null,
    ) {
    }
}
