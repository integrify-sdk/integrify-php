<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Data;

/**
 * Modifikator — yeməyə əlavə olunan seçim, məs. "Acılı".
 */
final readonly class Modifier extends Data
{
    /**
     * @param int|null $id Modifikatorun IDsi.
     * @param string|null $name Adı.
     * @param string|null $price Əlavə qiymət.
     * @param array<string, mixed>|null $ingredient Bağlı olduğu inqrediyent (sərbəst struktur).
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $price = null,
        public ?array $ingredient = null,
    ) {
    }
}
