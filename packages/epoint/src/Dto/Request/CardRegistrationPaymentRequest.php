<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `payAndSaveCard()` sorğusunun payload-u.
 *
 * `PaymentRequest`-dən yalnız bir şeylə fərqlənir: `other_attr` **yoxdur**. Bu
 * endpoint üçün EPoint onu qəbul etmir, və payload-dan `null` field-lər atılmadığı
 * üçün (bax: `EPointClient::payload()`) `PaymentRequest`-i təkrar istifadə etmək
 * məftilə `"other_attr": null` göndərərdi — Python kitabxanasının göndərmədiyi bir
 * field. `$description` isə burada məcburidir.
 */
final readonly class CardRegistrationPaymentRequest extends Data
{
    /**
     * @param string $amount Ödəniş məbləği, sətir formatında.
     * @param string $currency Məzənnə. Mümkün dəyər: `AZN`.
     * @param string $orderId Tətbiqinizdə unikal ID.
     * @param string $description Ödənişin təsviri. Bu endpoint üçün məcburidir.
     * @param string|null $successRedirectUrl Uğurlu ödənişdən sonra yönləndirilən URL.
     * @param string|null $errorRedirectUrl Uğursuz ödənişdən sonra yönləndirilən URL.
     */
    public function __construct(
        public string $amount,
        public string $currency,
        #[Field(name: 'order_id', maxLength: 255)]
        public string $orderId,
        #[Field(maxLength: 1000)]
        public string $description,
        #[Field(name: 'success_redirect_url')]
        public ?string $successRedirectUrl = null,
        #[Field(name: 'error_redirect_url')]
        public ?string $errorRedirectUrl = null,
    ) {
    }
}
