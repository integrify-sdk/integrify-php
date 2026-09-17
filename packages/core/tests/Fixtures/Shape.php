<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

use Integrify\Dto\Data;

/**
 * Serialisasiya kənar hallarını yoxlamaq üçün DTO.
 */
final readonly class Shape extends Data
{
    /**
     * @param list<string|null> $notes
     */
    public function __construct(
        public array $notes,
        public ?Blank $blank = null,
        public ?Size $size = null,
        public ?string $label = null,
    ) {
    }
}
