<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `partialReverseOrder()` tranzaksiyasının payload-u — avtorizasiyanın bir hissəsinin
 * ləğvi.
 */
final readonly class PartialReverseRequest extends Data
{
    /**
     * @param string $amount Ləğv ediləcək məbləğ, sətir formatında.
     * @param string $phase Tranzaksiyanın mərhələsi.
     * @param string $voidKind Ləğvin növü.
     */
    public function __construct(
        public string $amount,
        public string $phase = 'Single',
        #[Field(name: 'voidKind')]
        public string $voidKind = 'Partial',
    ) {
    }
}
