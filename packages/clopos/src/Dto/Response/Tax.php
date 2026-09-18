<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Data;

/**
 * Məhsula tətbiq olunan vergi.
 */
final readonly class Tax extends Data
{
    /**
     * @param int|null $id Verginin IDsi.
     * @param string|null $name Verginin adı.
     * @param string|null $rate Dərəcəsi.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $rate = null,
    ) {
    }
}
