<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Data;

/**
 * Keşbek balansı — `Balance`-ın qısa forması, vaxt damğaları olmadan.
 */
final readonly class CashbackBalance extends Data
{
    /**
     * @param string|null $name Balansın adı.
     * @param string|null $type Balansın növü.
     * @param string|null $amount Balansdakı məbləğ.
     */
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public ?string $amount = null,
    ) {
    }
}
