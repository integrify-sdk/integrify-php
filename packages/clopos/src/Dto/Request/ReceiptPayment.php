<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Request;

use Integrify\Dto\Data;

/**
 * `closeReceipt()`-də çekin bir ödəniş sətri.
 *
 * Çek bir neçə metodla bağlana bilər (bölünmüş ödəniş) — məbləğlərin cəmi çekin
 * qalığını örtməlidir.
 *
 * Məbləğ **sətir** verilir: `float` ötürmək qəpik dəqiqliyini itirə bilər.
 */
final readonly class ReceiptPayment extends Data
{
    /**
     * @param int $id Ödəniş metodunun IDsi.
     * @param string $name Metodun adı.
     * @param string $amount Bu metodla ödənilən məbləğ.
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $amount,
    ) {
    }
}
