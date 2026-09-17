<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Request;

use Integrify\Dto\Data;

/**
 * `refund()` sorğusunun payload-u.
 *
 * `amount` verilməsə tam geri qaytarma, verilsə yarımçıq geri qaytarma olur.
 */
final readonly class RefundRequest extends Data
{
    /**
     * @param string $transaction EPoint-in tranzaksiya IDsi. API-də field adı
     *     `transaction`-dır, Python kitabxanasında isə metod arqumenti
     *     `transaction_id` adlanır — burada da klient metodu `$transactionId`
     *     qəbul edir, məftildə `transaction` gedir.
     * @param string $currency Məzənnə. Mümkün dəyər: `AZN`.
     * @param string|null $amount Geri qaytarılacaq məbləğ, sətir formatında.
     *     `null` — tam geri qaytarma.
     */
    public function __construct(
        public string $transaction,
        public string $currency,
        public ?string $amount = null,
    ) {
    }
}
