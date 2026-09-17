<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `splitPay()` və `splitPayAndSaveCard()` sorğularının payload-u.
 *
 * Bölünmüş ödənişdə məbləğin bir hissəsi ikinci **EPoint istifadəçisinə** gedir.
 * Məftildə field `split_user` adlanır, dəyər isə həmin istifadəçinin IDsidir —
 * ona görə klient metodu `$splitUserId` qəbul edir.
 */
final readonly class SplitPaymentRequest extends Data
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
     * @param array<string, mixed>|null $otherAttr Callback-də geri qaytarılan əlavə dəyərlər.
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
        #[Field(name: 'other_attr')]
        public ?array $otherAttr = null,
    ) {
    }
}
