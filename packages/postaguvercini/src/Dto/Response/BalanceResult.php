<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\PostaGuvercini\Enum\StatusCode;

/**
 * `checkBalance()` cavabı.
 */
final readonly class BalanceResult extends Data
{
    /**
     * @param int|null $statusCode Sorğunun nəticə kodu. `200` uğur.
     * @param string|null $statusDescription Nəticənin izahı.
     * @param Balance|null $result Qalan kredit.
     */
    public function __construct(
        #[Field(name: 'StatusCode')]
        public ?int $statusCode = null,
        #[Field(name: 'StatusDescription')]
        public ?string $statusDescription = null,
        #[Field(name: 'Result')]
        public ?Balance $result = null,
    ) {
    }

    /** `statusCode`-un enum qarşılığı, tanınırsa. */
    public function status(): ?StatusCode
    {
        return $this->statusCode === null ? null : StatusCode::tryFrom($this->statusCode);
    }

    /** Yalnız `200` uğurdur. */
    public function isSuccessful(): bool
    {
        return $this->statusCode === StatusCode::Ok->value;
    }

    /**
     * Qalan kredit sayı; sorğu uğursuz olubsa `null`.
     *
     * `0` ilə `null` fərqlidir: birincisi "kredit bitib", ikincisi "bilinmir".
     */
    public function balance(): ?int
    {
        return $this->result?->balance;
    }
}
