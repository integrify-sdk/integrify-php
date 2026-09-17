<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Request;

use Integrify\Dto\Data;

/**
 * `getTransactionStatus()` sorğusunun payload-u.
 */
final readonly class TransactionStatusRequest extends Data
{
    /**
     * @param string $transaction EPoint-in tranzaksiya IDsi. Adətən `te` prefiksi ilə olur.
     */
    public function __construct(
        public string $transaction,
    ) {
    }
}
