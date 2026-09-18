<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Yeni sifarişin satış kanalı.
 *
 * Dörd field-in hamısı məcburidir: Clopos sifarişi həm satış növünə, həm də filiala
 * bağlayır və adları da sifarişin içində saxlayır (sonradan satış növü silinsə belə
 * sifariş oxunaqlı qalsın deyə).
 */
final readonly class NewOrderService extends Data
{
    /**
     * @param int $saleTypeId Satış növünün IDsi.
     * @param string $saleTypeName Satış növünün adı.
     * @param int $venueId Filialın IDsi.
     * @param string $venueName Filialın adı.
     */
    public function __construct(
        #[Field(name: 'sale_type_id')]
        public int $saleTypeId,
        #[Field(name: 'sale_type_name')]
        public string $saleTypeName,
        #[Field(name: 'venue_id')]
        public int $venueId,
        #[Field(name: 'venue_name')]
        public string $venueName,
    ) {
    }
}
