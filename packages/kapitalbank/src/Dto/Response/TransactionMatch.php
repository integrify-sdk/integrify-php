<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Tranzaksiyanın bank tərəfdəki identifikatorları.
 */
final readonly class TransactionMatch extends Data
{
    /**
     * @param string $tranActionId Tranzaksiyanın əməliyyat IDsi.
     * @param string $ridByPmo PMO tərəfindən verilmiş identifikator.
     */
    public function __construct(
        #[Field(name: 'tranActionId')]
        public string $tranActionId,
        #[Field(name: 'ridByPmo')]
        public string $ridByPmo,
    ) {
    }
}
