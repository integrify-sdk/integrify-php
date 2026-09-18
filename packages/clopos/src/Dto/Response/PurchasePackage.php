<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Data;

/**
 * İnqrediyentin alış bağlaması — məs. "10 ədədlik qutu".
 *
 * `equal` bağlamanın neçə baza vahidinə bərabər olduğunu göstərir.
 */
final readonly class PurchasePackage extends Data
{
    /**
     * @param int|null $id Bağlamanın IDsi.
     * @param string|null $name Bağlamanın adı.
     * @param int|null $equal Neçə baza vahidi.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?int $equal = null,
    ) {
    }
}
