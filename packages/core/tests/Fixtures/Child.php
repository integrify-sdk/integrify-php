<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/** Nested DTO testləri üçün alt DTO. */
final readonly class Child extends Data
{
    public function __construct(
        #[Field(name: 'childNamE', maxLength: 10)]
        public string $name,
        #[Field(name: 'childId')]
        public ?int $id = null,
    ) {
    }
}
