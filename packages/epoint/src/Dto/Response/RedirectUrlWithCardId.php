<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\EPoint\Enum\TransactionStatus;

/**
 * Kart qeydiyyatı ilə müşayiət olunan cavab — `saveCard()`, `payAndSaveCard()` və
 * `splitPayAndSaveCard()` bunu qaytarır.
 *
 * `cardId` **hələ istifadəyə hazır deyil**: müştəri `redirectUrl`-də kartı uğurla
 * qeyd etməyincə onunla ödəniş etmək mümkün deyil. Təsdiq callback ilə gəlir.
 */
final readonly class RedirectUrlWithCardId extends Data
{
    /**
     * @param string|null $status Əməliyyatın nəticəsi.
     * @param string|null $message Əməliyyatın icra statusu haqqında mesaj.
     * @param string|null $transaction EPoint xidmətinin əməliyyat IDsi.
     * @param string|null $redirectUrl Müştərinin yönləndirilməsi lazım olan URL.
     * @param string|null $cardId Sonrakı ödənişlərdə istifadə olunacaq kart identifikatoru.
     */
    public function __construct(
        public ?string $status = null,
        public ?string $message = null,
        public ?string $transaction = null,
        #[Field(name: 'redirect_url')]
        public ?string $redirectUrl = null,
        #[Field(name: 'card_id')]
        public ?string $cardId = null,
    ) {
    }

    public function status(): ?TransactionStatus
    {
        return TransactionStatus::parse($this->status);
    }

    public function isSuccessful(): bool
    {
        return TransactionStatus::succeeded($this->status);
    }
}
