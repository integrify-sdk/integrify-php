<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\EPoint\Enum\TransactionStatus;

/**
 * Müştərinin yönləndirilməsi lazım gələn cavab — `pay()` və `splitPay()` bunu qaytarır.
 *
 * `redirectUrl` ödənişin **başlanğıcıdır**, nəticəsi deyil. Müştəri həmin URL-ə
 * daxil olub kart məlumatlarını yazır; nəticə isə EPoint dashboard-ında qeyd
 * etdiyiniz callback URL-inə gəlir və `Callback::decode()` ilə açılır.
 */
final readonly class RedirectUrl extends Data
{
    /**
     * @param string|null $status Əməliyyatın nəticəsi.
     * @param string|null $message Əməliyyatın icra statusu haqqında mesaj.
     * @param string|null $transaction EPoint xidmətinin əməliyyat IDsi.
     * @param string|null $redirectUrl Müştərinin yönləndirilməsi lazım olan URL.
     */
    public function __construct(
        public ?string $status = null,
        public ?string $message = null,
        public ?string $transaction = null,
        #[Field(name: 'redirect_url')]
        public ?string $redirectUrl = null,
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
