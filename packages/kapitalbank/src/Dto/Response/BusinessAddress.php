<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Merchant-ın qeydiyyat ünvanı.
 */
final readonly class BusinessAddress extends Data
{
    /**
     * @param string|null $country Ölkənin adı.
     * @param string|null $countryA2 ISO 3166-1 alpha-2 kodu.
     * @param int|null $countryN3 ISO 3166-1 numeric kodu.
     */
    public function __construct(
        public ?string $country = null,
        #[Field(name: 'countryA2')]
        public ?string $countryA2 = null,
        #[Field(name: 'countryN3')]
        public ?int $countryN3 = null,
    ) {
    }
}
