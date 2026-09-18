<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Kartın məlumatları.
 */
final readonly class CardDetails extends Data
{
    /**
     * @param string|null $expiration Kartın bitmə tarixi.
     * @param string|null $brand Ödəniş sistemi (`VISA`, `MC`).
     * @param CardAuthentication|null $authentication 3-D Secure nəticəsi.
     * @param string|null $issuerRid Emitent bankın identifikatoru.
     */
    public function __construct(
        public ?string $expiration = null,
        public ?string $brand = null,
        public ?CardAuthentication $authentication = null,
        #[Field(name: 'issuerRid')]
        public ?string $issuerRid = null,
    ) {
    }
}
