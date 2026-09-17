<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `pay()` və `payAndSaveCard()` sorğularının payload-u.
 *
 * `amount` **sətirdir**, `float` deyil. EPoint məbləği JSON sətri kimi gözləyir
 * (Python kitabxanası `Decimal`-ı pydantic-in json rejimi ilə serialize edir və
 * nəticə `"100.50"` olur), və `float` yuvarlaqlaşma itkisi ilə `10.55` yerinə
 * `10.550000000000001` göndərə bilər. Klient metodları `int|float|string` qəbul
 * edib bura normallaşdırılmış sətir ötürür.
 */
final readonly class PaymentRequest extends Data
{
    /**
     * @param string $amount Ödəniş məbləği, sətir formatında (`'100'`, `'10.50'`).
     * @param string $currency Məzənnə. Mümkün dəyər: `AZN`.
     * @param string $orderId Tətbiqinizdə unikal ID.
     * @param string|null $successRedirectUrl Uğurlu ödənişdən sonra yönləndirilən URL.
     * @param string|null $errorRedirectUrl Uğursuz ödənişdən sonra yönləndirilən URL.
     * @param string|null $description Ödənişin təsviri.
     * @param array<string, mixed>|null $otherAttr Callback-də geri qaytarılan əlavə dəyərlər.
     */
    public function __construct(
        public string $amount,
        public string $currency,
        #[Field(name: 'order_id', maxLength: 255)]
        public string $orderId,
        #[Field(name: 'success_redirect_url')]
        public ?string $successRedirectUrl = null,
        #[Field(name: 'error_redirect_url')]
        public ?string $errorRedirectUrl = null,
        #[Field(maxLength: 1000)]
        public ?string $description = null,
        #[Field(name: 'other_attr')]
        public ?array $otherAttr = null,
    ) {
    }
}
