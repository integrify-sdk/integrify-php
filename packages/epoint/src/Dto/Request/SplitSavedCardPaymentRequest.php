<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `splitPayWithSavedCard()` sorğusunun payload-u.
 */
final readonly class SplitSavedCardPaymentRequest extends Data
{
    /**
     * @param string $amount Ümumi ödəniş məbləği, sətir formatında.
     * @param string $currency Məzənnə. Mümkün dəyər: `AZN`.
     * @param string $orderId Tətbiqinizdə unikal ID.
     * @param string $cardId Saxlanılmış kartın IDsi.
     * @param string $splitUser Ödənişin bölündüyü EPoint istifadəçisinin IDsi.
     * @param string $splitAmount Həmin istifadəçiyə gedən məbləğ, sətir formatında.
     * @param string|null $description Ödənişin təsviri.
     */
    public function __construct(
        public string $amount,
        public string $currency,
        #[Field(name: 'order_id', maxLength: 255)]
        public string $orderId,
        #[Field(name: 'card_id')]
        public string $cardId,
        #[Field(name: 'split_user')]
        public string $splitUser,
        #[Field(name: 'split_amount')]
        public string $splitAmount,
        #[Field(maxLength: 1000)]
        public ?string $description = null,
    ) {
    }
}
