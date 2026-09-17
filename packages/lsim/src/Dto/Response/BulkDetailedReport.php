<?php

declare(strict_types=1);

namespace Integrify\Lsim\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\Lsim\Enum\BulkCode;

/**
 * Detallı hesabat: nəticə kodu + hər SMS üçün bir sətir.
 */
final readonly class BulkDetailedReport extends Data
{
    /**
     * @param int $responseCode Sorğunun nəticə kodu. Enum üçün `code()`.
     * @param list<BulkSmsReport> $reports Hər SMS üçün bir element.
     */
    public function __construct(
        public int $responseCode,
        #[Field(of: BulkSmsReport::class)]
        public array $reports = [],
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
