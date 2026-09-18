<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `fullReverseOrder()` tranzaksiyasının payload-u — avtorizasiyanın tam ləğvi.
 *
 * Məbləğ yoxdur: tam ləğv bütün avtorizasiya olunmuş məbləği qaytarır.
 */
final readonly class FullReverseRequest extends Data
{
    /**
     * @param string $phase Tranzaksiyanın mərhələsi.
     * @param string $voidKind Ləğvin növü.
     */
    public function __construct(
        public string $phase = 'Auth',
        #[Field(name: 'voidKind')]
        public string $voidKind = 'Full',
    ) {
    }
}
