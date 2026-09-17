<?php

declare(strict_types=1);

namespace Integrify\Lsim\Dto\Response;

use Integrify\Dto\Data;
use Integrify\Lsim\Enum\SmsCode;

/**
 * Tək SMS API-sinin ümumi cavabı (GET endpoint-ləri və balans).
 *
 * `obj` field-inin mənası sorğudan asılıdır:
 *
 * - `sendSms()` — uğurlu göndərilmədə **transaction id**;
 * - `balance()` — qalan **SMS sayı**.
 */
final readonly class SmsResult extends Data
{
    /**
     * @param string|null $successMessage Uğurlu sorğu mesajı.
     * @param string|null $errorMessage Xəta mesajı.
     * @param int|null $obj Sorğudan asılı dəyər (transaction id və ya balans).
     * @param int|null $errorCode Status kodu. Enum üçün `code()` metoduna baxın.
     */
    public function __construct(
        public ?string $successMessage = null,
        public ?string $errorMessage = null,
        public ?int $obj = null,
        public ?int $errorCode = null,
    ) {
    }

    /**
     * Status kodunun enum qarşılığı, tanınırsa.
     *
     * DTO xam `int` saxlayır ki, LSIM yeni kod əlavə etdikdə validasiya sınmasın.
     */
    public function code(): ?SmsCode
    {
        return $this->errorCode === null ? null : SmsCode::tryFrom($this->errorCode);
    }

    /**
     * Mənfi kod xətadır; kod yoxdursa, `errorMessage`-ə baxılır.
     */
    public function isSuccessful(): bool
    {
        if ($this->errorCode !== null) {
            return $this->errorCode >= 0;
        }

        return $this->errorMessage === null || $this->errorMessage === '';
    }
}
