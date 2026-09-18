<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Request;

use Integrify\Dto\Data;

/**
 * `processPaymentWithSavedCard()` tranzaksiyasının payload-u.
 *
 * `conditions.cofUsage = 'Cit'` — Customer Initiated Transaction, yəni müştəri
 * ödənişi özü başladır (abunə kimi avtomatik yox).
 */
final readonly class SavedCardPaymentRequest extends Data
{
    /**
     * @param string $amount Ödəniş məbləği, sətir formatında.
     * @param string|null $phase Tranzaksiyanın mərhələsi.
     * @param array<string, string>|null $conditions Kartın istifadə şərtləri.
     */
    public function __construct(
        public string $amount,
        public ?string $phase = 'Single',
        public ?array $conditions = ['cofUsage' => 'Cit'],
    ) {
    }
}
