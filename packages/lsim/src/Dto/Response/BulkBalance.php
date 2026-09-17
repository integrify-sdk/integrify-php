<?php

declare(strict_types=1);

namespace Integrify\Lsim\Dto\Response;

use Integrify\Dto\Data;
use Integrify\Lsim\Enum\BulkCode;

/**
 * Toplu SMS hesabının balansı.
 */
final readonly class BulkBalance extends Data
{
    /**
     * @param int $responseCode Sorğunun nəticə kodu. Enum üçün `code()`.
     * @param int $units Qalan SMS sayı. `-1` — LSIM dəyəri qaytarmadı.
     */
    public function __construct(
        public int $responseCode,
        public int $units = -1,
    ) {
    }

    public function code(): ?BulkCode
    {
        return BulkCode::tryFrom($this->responseCode);
    }

    public function isSuccessful(): bool
    {
        return $this->responseCode === BulkCode::Success->value;
    }
}
