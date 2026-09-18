<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Data;

/**
 * Sifariş növünün qısa təsviri.
 */
final readonly class OrderType extends Data
{
    /**
     * @param string $title Növün adı.
     */
    public function __construct(
        public string $title,
    ) {
    }
}
