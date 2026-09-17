<?php

declare(strict_types=1);

namespace Integrify\Lsim\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `deliveryReport()` (POST) cavabı.
 */
final readonly class DeliveryReport extends Data
{
    /**
     * @param string|null $message Xəta və ya uğur mesajı.
     * @param string|null $deliveryStatus SMS statusu (məs., `DELIVERED`).
     */
    public function __construct(
        public ?string $message = null,
        #[Field(name: 'delivery_status')]
        public ?string $deliveryStatus = null,
    ) {
    }
}
