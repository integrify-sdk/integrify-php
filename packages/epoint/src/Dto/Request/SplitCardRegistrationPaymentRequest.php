<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `splitPayAndSaveCard()` sorğusunun payload-u.
 *
 * `SplitPaymentRequest`-dən `other_attr`-ın olmaması ilə fərqlənir — səbəbi
 * [`CardRegistrationPaymentRequest`](CardRegistrationPaymentRequest.php)-də izah olunub.
 */
final readonly class SplitCardRegistrationPaymentRequest extends Data
{
    /**
     * @param string $amount Ümumi ödəniş məbləği, sətir formatında.
     * @param string $currency Məzənnə. Mümkün dəyər: `AZN`.
     * @param string $orderId Tətbiqinizdə unikal ID.
     * @param string $splitUser Ödənişin bölündüyü EPoint istifadəçisinin IDsi.
     * @param string $splitAmount Həmin istifadəçiyə gedən məbləğ, sətir formatında.
     * @param string|null $successRedirectUrl Uğurlu ödənişdən sonra yönləndirilən URL.
     * @param string|null $errorRedirectUrl Uğursuz ödənişdən sonra yönləndirilən URL.
     * @param string|null $description Ödənişin təsviri.
     */
    public function __construct(
        public string $amount,
        public string $currency,
        #[Field(name: 'order_id', maxLength: 255)]
        public string $orderId,
        #[Field(name: 'split_user')]
        public string $splitUser,
        #[Field(name: 'split_amount')]
        public string $splitAmount,
        #[Field(name: 'success_redirect_url')]
        public ?string $successRedirectUrl = null,
        #[Field(name: 'error_redirect_url')]
        public ?string $errorRedirectUrl = null,
        #[Field(maxLength: 1000)]
        public ?string $description = null,
    ) {
    }
}
