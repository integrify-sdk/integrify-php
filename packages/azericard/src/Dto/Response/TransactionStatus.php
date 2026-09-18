<?php

declare(strict_types=1);

namespace Integrify\Azericard\Dto\Response;

use Integrify\Azericard\Enum\Action;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `getTransactionStatus()` cavabı.
 *
 * Field adlarına diqqət: Azericard bu cavabda **boşluqlu, insan üçün yazılmış**
 * açarlar qaytarır (`"Transaction Status message"`, `"Banks approval code"`),
 * digər endpoint-lərdəki `UPPER_SNAKE` deyil. Adlar olduğu kimi saxlanılıb.
 */
final readonly class TransactionStatus extends Data
{
    /**
     * @param int|null $action E-Gateway fəaliyyət kodu. Enum üçün `action()`.
     * @param string|null $responseCode ISO-8583 Sahə 39 cavab kodu.
     * @param string|null $statusMessage Vəziyyət mesajı.
     * @param string|null $terminal Terminal IDsi.
     * @param string|null $cardNumber Maskalanmış kart nömrəsi.
     * @param string|null $amount Əməliyyatın məbləği. **Sətir** saxlanılır.
     * @param string|null $currency Əməliyyatın valyutası.
     * @param string|null $date Əməliyyatın tarixi, `YYYYMMDDHHMMSS`.
     * @param string|null $state Əməliyyatın vəziyyəti.
     * @param string|null $order Sifariş IDsi.
     * @param string|null $approval Bankın təsdiq kodu.
     * @param string|null $rrn Retrieval Reference Number.
     * @param string|null $internalReference E-ticarət şlüzünün daxili istinadı.
     * @param string|null $originalType Orijinal əməliyyatın `TRTYPE` dəyəri.
     * @param string|null $timestamp Sorğunun vaxtı.
     * @param string|null $nonce Orijinal əməliyyatın nonce dəyəri.
     * @param string|null $signature Azericard-ın MAC dəyəri (`P_SIGN`).
     */
    public function __construct(
        #[Field(name: 'ACTION')]
        public ?int $action = null,
        #[Field(name: 'Response code')]
        public ?string $responseCode = null,
        #[Field(name: 'Transaction Status message')]
        public ?string $statusMessage = null,
        #[Field(name: 'TERMINAL')]
        public ?string $terminal = null,
        #[Field(name: 'Card number')]
        public ?string $cardNumber = null,
        #[Field(name: 'Transaction amount')]
        public ?string $amount = null,
        #[Field(name: 'Transaction currency')]
        public ?string $currency = null,
        #[Field(name: 'Transaction date')]
        public ?string $date = null,
        #[Field(name: 'Transaction state')]
        public ?string $state = null,
        #[Field(name: 'Merchant order id')]
        public ?string $order = null,
        #[Field(name: 'Banks approval code')]
        public ?string $approval = null,
        #[Field(name: 'Transaction RRN')]
        public ?string $rrn = null,
        #[Field(name: 'INT_REF')]
        public ?string $internalReference = null,
        #[Field(name: 'Original transaction TRTYPE')]
        public ?string $originalType = null,
        #[Field(name: 'Timestamp')]
        public ?string $timestamp = null,
        #[Field(name: 'Nonce')]
        public ?string $nonce = null,
        #[Field(name: 'P_SIGN')]
        public ?string $signature = null,
    ) {
    }

    /** `action`-un enum qarşılığı, tanınırsa. */
    public function action(): ?Action
    {
        return $this->action === null ? null : Action::tryFrom($this->action);
    }

    /** Əməliyyat uğurlu oldumu. */
    public function isSuccessful(): bool
    {
        return $this->action === Action::Success->value;
    }
}
