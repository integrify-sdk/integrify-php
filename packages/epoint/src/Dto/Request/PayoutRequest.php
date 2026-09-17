<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `payout()` sorğusunun payload-u — saxlanılmış karta pul köçürülməsi.
 *
 * Endpoint `/api/1/refund-request` adlanır, lakin bu **refund deyil**: pul sizin
 * balansınızdan müştərinin kartına gedir. Ödənişin geri qaytarılması üçün
 * [`RefundRequest`](RefundRequest.php).
 */
final readonly class PayoutRequest extends Data
{
    /**
     * @param string $amount Nağdlaşdırılacaq məbləğ, sətir formatında.
     * @param string $currency Məzənnə. Mümkün dəyər: `AZN`.
     * @param string $orderId Tətbiqinizdə unikal ID.
     * @param string $cardId Saxlanılmış kartın IDsi.
     * @param string|null $description Nağdlaşdırmanın təsviri.
     */
    public function __construct(
        public string $amount,
        public string $currency,
        #[Field(name: 'order_id', maxLength: 255)]
        public string $orderId,
        #[Field(name: 'card_id')]
        public string $cardId,
        #[Field(maxLength: 1000)]
        public ?string $description = null,
    ) {
    }
}
