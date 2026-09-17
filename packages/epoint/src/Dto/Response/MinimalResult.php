<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Response;

use Integrify\Dto\Data;
use Integrify\EPoint\Enum\TransactionStatus;

/**
 * EPoint cavablarının ən kiçik forması — `refund()` bunu qaytarır.
 *
 * **EPoint həmişə HTTP 200 qaytarır**, xəta halında da. Yəni `RequestFailed`
 * atılmaması ödənişin alındığı demək deyil: nəticə `status` field-indədir və
 * `isSuccessful()` ilə oxunur.
 */
final readonly class MinimalResult extends Data
{
    /**
     * @param string|null $status `success`, `error`, `server_error` və ya `failed`.
     *     Enum üçün `status()` metoduna baxın.
     * @param string|null $message Əməliyyatın icra statusu haqqında mesaj.
     */
    public function __construct(
        public ?string $status = null,
        public ?string $message = null,
    ) {
    }

    /** `status`-un enum qarşılığı, tanınırsa. */
    public function status(): ?TransactionStatus
    {
        return TransactionStatus::parse($this->status);
    }

    /** Yalnız `success` uğurdur. */
    public function isSuccessful(): bool
    {
        return TransactionStatus::succeeded($this->status);
    }
}
