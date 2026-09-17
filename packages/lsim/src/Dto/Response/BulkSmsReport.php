<?php

declare(strict_types=1);

namespace Integrify\Lsim\Dto\Response;

use Integrify\Dto\Data;
use Integrify\Lsim\Enum\SmsStatus;

/**
 * Detallı hesabatda bir SMS-in vəziyyəti.
 */
final readonly class BulkSmsReport extends Data
{
    /**
     * @param string $msisdn Nömrə. LSIM onu rəqəm kimi qaytarır, lakin başında sıfır
     *     ola biləcəyi üçün burada sətir kimi saxlanılır.
     * @param string $message Göndərilmiş mesaj.
     * @param int $status SMS statusu. Enum üçün `smsStatus()`.
     * @param string|null $date Yalnız `detailedReportWithDates()` cavabında olur.
     */
    public function __construct(
        public string $msisdn,
        public string $message,
        public int $status,
        public ?string $date = null,
    ) {
    }

    public function smsStatus(): ?SmsStatus
    {
        return SmsStatus::tryFrom($this->status);
    }

    public function isDelivered(): bool
    {
        return $this->status === SmsStatus::Delivered->value;
    }
}
