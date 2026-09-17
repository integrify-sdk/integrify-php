<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/** Mapper-in bütün imkanlarını yoxlayan DTO. */
final readonly class Sample extends Data
{
    /**
     * @param list<Child> $children
     */
    public function __construct(
        #[Field(name: 'namE_OF_THING', maxLength: 5, minLength: 2)]
        public string $name,
        #[Field(of: Child::class, maxItems: 2)]
        public array $children = [],
        public ?Colour $colour = null,
        public ?Level $level = null,
        #[Field(min: 1, max: 10)]
        public ?int $score = null,
        public ?bool $active = null,
        #[Field(pattern: '/^[A-Z]{2}$/')]
        public ?string $country = null,
    ) {
    }
}
