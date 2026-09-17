<?php

declare(strict_types=1);

namespace Integrify\Lsim\Dto\Response;

use Integrify\Lsim\Enum\SmsCode;

/**
 * `checkStatus()` (GET) cavabı.
 *
 * Bu endpoint JSON qaytarmır — body-si sadəcə bir ədəddir (məs., `101`). Ona görə
 * `Data`-dan törəmir: hidrasiya ediləcək massiv yoxdur, xam body parse olunur.
 */
final readonly class ReportStatus
{
    /**
     * @param int|null $errorCode Status kodu, body ədəd deyilsə `null`.
     */
    public function __construct(public ?int $errorCode = null)
    {
    }

    /**
     * Xam cavab body-sindən qurur.
     *
     * Body boş, mətn, və ya gözlənilməz formatda ola bilər (gateway xətası zamanı
     * HTML səhifə) — belə halda `errorCode` `null` olur, exception atılmır.
     */
    public static function fromBody(string $body): self
    {
        $trimmed = trim($body);

        if ($trimmed === '' || filter_var($trimmed, FILTER_VALIDATE_INT) === false) {
            return new self(null);
        }

        return new self((int) $trimmed);
    }

    /**
     * Status kodunun enum qarşılığı, tanınırsa.
     */
    public function code(): ?SmsCode
    {
        return $this->errorCode === null ? null : SmsCode::tryFrom($this->errorCode);
    }

    public function isDelivered(): bool
    {
        return $this->errorCode === SmsCode::Delivered->value;
    }
}
