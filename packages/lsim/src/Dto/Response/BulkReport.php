<?php

declare(strict_types=1);

namespace Integrify\Lsim\Dto\Response;

use Integrify\Dto\Data;
use Integrify\Lsim\Enum\BulkCode;

/**
 * Toplu göndərilmənin yekun hesabatı — hər statusdan neçə SMS olduğu.
 *
 * `-1` "LSIM bu sahəni qaytarmadı" deməkdir, sıfır yox.
 */
final readonly class BulkReport extends Data
{
    public function __construct(
        public int $responseCode,
        public int $expired = -1,
        public int $removed = -1,
        public int $blackList = -1,
        public int $undelivered = -1,
        public int $delivered = -1,
        public int $duplicate = -1,
        public int $error = -1,
        public int $send = -1,
        public int $queue = -1,
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
