<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Data;

/**
 * Çekin ödəniş sətri — bir metodla ödənilmiş məbləğ.
 *
 * Bir çek bir neçə metodla ödənilə bilər (bölünmüş ödəniş), ona görə bu siyahıdır.
 */
final readonly class ReceiptPaymentMethod extends Data
{
    /**
     * @param int|null $id Ödəniş metodunun IDsi.
     * @param string|null $name Metodun adı.
     * @param string|null $amount Bu metodla ödənilən məbləğ.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $amount = null,
    ) {
    }
}
