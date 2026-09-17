<?php

declare(strict_types=1);

namespace Integrify\Lsim\Dto\Response;

use Integrify\Dto\Data;

/**
 * `sendSmsPost()` cavabı.
 *
 * GET variantından yeganə fərqi: LSIM burada `errorCode`-u **ad** kimi qaytarır
 * (məs., `INTERNAL_ERROR`), rəqəm kimi yox.
 */
final readonly class SmsPostResult extends Data
{
    /**
     * @param string|null $successMessage Uğurlu sorğu mesajı.
     * @param string|null $errorMessage Xəta mesajı.
     * @param int|null $obj Uğurlu göndərilmədə transaction id.
     * @param string|null $errorCode Status **adı** (məs., `INTERNAL_ERROR`).
     */
    public function __construct(
        public ?string $successMessage = null,
        public ?string $errorMessage = null,
        public ?int $obj = null,
        public ?string $errorCode = null,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->errorMessage === null || $this->errorMessage === '';
    }
}
