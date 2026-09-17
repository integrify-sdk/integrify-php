<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `iterable` və enum siyahılarını yoxlamaq üçün DTO.
 */
final readonly class Tagged extends Data
{
    /**
     * @param list<Level> $levels
     * @param list<Colour> $colours
     * @param iterable<Child> $stream
     */
    public function __construct(
        #[Field(of: Level::class)]
        public array $levels,
        #[Field(of: Colour::class)]
        public array $colours = [],
        #[Field(of: Child::class)]
        public iterable $stream = [],
        public ?Level $level = null,
    ) {
    }
}
