<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

use Integrify\Dto\Data;

/**
 * Bütün field-ləri opsional olan DTO — boş serialisasiya davranışını yoxlamaq üçün.
 */
final readonly class Blank extends Data
{
    public function __construct(
        public ?string $note = null,
    ) {
    }
}
