<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Hesabdakı qalan kredit.
 */
final readonly class Balance extends Data
{
    /**
     * @param int|null $balance Qalan kredit sayı.
     */
    public function __construct(
        #[Field(name: 'Balance')]
        public ?int $balance = null,
    ) {
    }
}
