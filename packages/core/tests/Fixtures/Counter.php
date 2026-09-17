<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

use Integrify\Dto\Data;

/** Yalnız ədəd sahəsi olan sadə DTO. */
final readonly class Counter extends Data
{
    public function __construct(
        public int $total = 0,
    ) {
    }
}
