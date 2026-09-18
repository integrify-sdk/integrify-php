<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Request;

use Integrify\Dto\Data;

/**
 * `clearingOrder()` tranzaksiyasının payload-u — avtorizasiya olunmuş məbləğin
 * faktiki silinməsi.
 */
final readonly class ClearingRequest extends Data
{
    /**
     * @param string $amount Silinəcək məbləğ, sətir formatında.
     * @param string $phase Tranzaksiyanın mərhələsi.
     */
    public function __construct(
        public string $amount,
        public string $phase = 'Clearing',
    ) {
    }
}
