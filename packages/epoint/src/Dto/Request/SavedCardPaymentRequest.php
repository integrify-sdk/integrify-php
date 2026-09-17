<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `payWithSavedCard()` sorğusunun payload-u.
 *
 * Yönləndirmə URL-ləri yoxdur: ödəniş server tərəfdə, müştəri iştirakı olmadan
 * icra olunur, ona görə yönləndiriləcək yer də yoxdur.
 */
final readonly class SavedCardPaymentRequest extends Data
{
    /**
     * @param string $amount Ödəniş məbləği, sətir formatında.
     * @param string $currency Məzənnə. Mümkün dəyər: `AZN`.
     * @param string $orderId Tətbiqinizdə unikal ID.
     * @param string $cardId Saxlanılmış kartın IDsi. Adətən `ce` prefiksi ilə başlayır.
     */
    public function __construct(
        public string $amount,
        public string $currency,
        #[Field(name: 'order_id', maxLength: 255)]
        public string $orderId,
        #[Field(name: 'card_id')]
        public string $cardId,
    ) {
    }
}
