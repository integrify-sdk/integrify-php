<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Sifarişin satış kanalı — hansı filialda, hansı satış növü ilə.
 */
final readonly class SaleService extends Data
{
    /**
     * @param int|null $saleTypeId Satış növünün IDsi.
     * @param string|null $saleTypeName Satış növünün adı.
     * @param int|null $venueId Filialın IDsi.
     * @param string|null $venueName Filialın adı.
     */
    public function __construct(
        #[Field(name: 'sale_type_id')]
        public ?int $saleTypeId = null,
        #[Field(name: 'sale_type_name')]
        public ?string $saleTypeName = null,
        #[Field(name: 'venue_id')]
        public ?int $venueId = null,
        #[Field(name: 'venue_name')]
        public ?string $venueName = null,
    ) {
    }
}
