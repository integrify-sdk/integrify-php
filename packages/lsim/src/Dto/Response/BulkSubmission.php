<?php

declare(strict_types=1);

namespace Integrify\Lsim\Dto\Response;

use Integrify\Dto\Data;
use Integrify\Lsim\Enum\BulkCode;

/**
 * Toplu göndərilmə sorğusunun cavabı.
 */
final readonly class BulkSubmission extends Data
{
    /**
     * @param int $responseCode Sorğunun nəticə kodu. Enum üçün `code()`.
     * @param int|null $taskId Hesabat sorğularında istifadə olunan tapşırıq id-si.
     */
    public function __construct(
        public int $responseCode,
        public ?int $taskId = null,
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
