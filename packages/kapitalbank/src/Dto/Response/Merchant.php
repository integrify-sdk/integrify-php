<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Ödənişi qəbul edən merchant.
 */
final readonly class Merchant extends Data
{
    /**
     * @param int|null $id Merchant IDsi.
     * @param string|null $rid Merchant-ın identifikatoru.
     * @param string|null $title Merchant-ın adı.
     * @param BusinessAddress|null $businessAddress Qeydiyyat ünvanı.
     * @param bool|null $trustConsumerPhone Müştərinin telefonuna etibar olunurmu.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $rid = null,
        public ?string $title = null,
        #[Field(name: 'businessAddress')]
        public ?BusinessAddress $businessAddress = null,
        #[Field(name: 'trustConsumerPhone')]
        public ?bool $trustConsumerPhone = null,
    ) {
    }
}
