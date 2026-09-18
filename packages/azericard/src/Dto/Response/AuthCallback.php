<?php

declare(strict_types=1);

namespace Integrify\Azericard\Dto\Response;

use Integrify\Azericard\Enum\Action;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Kart əməliyyatının nəticəsi — Azericard-ın callback URL-inizə post etdiyi data.
 *
 * `token` və `card` yalnız `authorizeAndSaveCard()` axınında gəlir.
 *
 * > [!WARNING]
 * > Bu callback-in `P_SIGN` dəyəri **yoxlanıla bilmir**: o, Azericard-ın öz açarı ilə
 * > imzalanıb və yoxlamaq üçün onların **public** açarı lazımdır, sizin private açarınız
 * > deyil. Python kitabxanası da yoxlamır. Ona görə callback-i tək başına "ödəniş
 * > oldu" sübutu saymayın — `getTransactionStatus()` ilə təsdiqləyin.
 */
final readonly class AuthCallback extends Data
{
    /**
     * @param string|null $order Sifariş IDsi.
     * @param string|null $amount Məbləğ. **Sətir** saxlanılır.
     * @param string|null $currency Valyuta.
     * @param string|null $terminal Terminal IDsi.
     * @param string|null $type Əməliyyatın `TRTYPE` dəyəri.
     * @param int|null $action E-Gateway fəaliyyət kodu. Enum üçün `action()`.
     * @param string|null $responseCode ISO-8583 Sahə 39 cavab kodu.
     * @param string|null $approval Bankın təsdiq kodu; bank verməyibsə boş ola bilər.
     * @param string|null $rrn Retrieval Reference Number — `finalize()` üçün lazımdır.
     * @param string|null $internalReference Daxili istinad — `finalize()` üçün lazımdır.
     * @param string|null $timestamp Əməliyyatın vaxtı.
     * @param string|null $nonce Əməliyyatın nonce dəyəri.
     * @param string|null $signature Azericard-ın MAC dəyəri.
     * @param string|null $card Maskalanmış kart nömrəsi — yalnız kart yaddaşı axınında.
     * @param string|null $token Saxlanılan kartın tokeni — yalnız kart yaddaşı axınında.
     */
    public function __construct(
        #[Field(name: 'ORDER')]
        public ?string $order = null,
        #[Field(name: 'AMOUNT')]
        public ?string $amount = null,
        #[Field(name: 'CURRENCY')]
        public ?string $currency = null,
        #[Field(name: 'TERMINAL')]
        public ?string $terminal = null,
        #[Field(name: 'TRTYPE')]
        public ?string $type = null,
        #[Field(name: 'ACTION')]
        public ?int $action = null,
        #[Field(name: 'RC')]
        public ?string $responseCode = null,
        #[Field(name: 'APPROVAL')]
        public ?string $approval = null,
        #[Field(name: 'RRN')]
        public ?string $rrn = null,
        #[Field(name: 'INT_REF')]
        public ?string $internalReference = null,
        #[Field(name: 'TIMESTAMP')]
        public ?string $timestamp = null,
        #[Field(name: 'NONCE')]
        public ?string $nonce = null,
        #[Field(name: 'P_SIGN')]
        public ?string $signature = null,
        #[Field(name: 'CARD')]
        public ?string $card = null,
        #[Field(name: 'TOKEN')]
        public ?string $token = null,
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
