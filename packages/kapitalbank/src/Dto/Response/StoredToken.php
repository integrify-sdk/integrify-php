<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Card-on-File provayderində saxlanılmış token.
 */
final readonly class StoredToken extends Data
{
    /**
     * @param int|null $id Token nömrəsi.
     * @param string|null $cofProviderRid CoF provayderinin identifikatoru.
     * @param string|null $ridByCofp Provayderin verdiyi identifikator.
     */
    public function __construct(
        public ?int $id = null,
        #[Field(name: 'cofProviderRid')]
        public ?string $cofProviderRid = null,
        #[Field(name: 'ridBycofp')]
        public ?string $ridByCofp = null,
    ) {
    }
}
