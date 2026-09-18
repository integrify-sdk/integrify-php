<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Kartı da qeyd edən `/api/order` sorğularının payload-u.
 *
 * | Metod | `typeRid` | `hppCofCapturePurposes` | `aut` |
 * | :--- | :--- | :--- | :--- |
 * | `saveCard()` | `Order_DMS` | `['Cit', 'Recurring']` | `{purpose: AddCard}` |
 * | `payAndSaveCard()` | `Order_SMS` | `['Cit']` | `{purpose: AddCard}` |
 *
 * [`OrderRequest`](OrderRequest.php)-dən yalnız `aut` field-i ilə fərqlənir. Ayrı
 * DTO olmasının səbəbi budur: `null` field-lər payload-dan atılmır (bax:
 * `KapitalClient::order()`), ona görə `aut`-u ortaq DTO-ya qoymaq `createOrder()`
 * sorğusuna Python-un göndərmədiyi `"aut": null` açarını əlavə edərdi.
 */
final readonly class CardRegistrationOrderRequest extends Data
{
    /**
     * @param string $amount Ödəniş məbləği, sətir formatında.
     * @param string $currency Məzənnə. Mümkün dəyərlər: `AZN`, `USD`.
     * @param string $description Ödənişin təsviri.
     * @param array<string, string> $aut Kart qeydiyyatının məqsədi.
     * @param string|null $language Ödəniş səhifəsinin dili.
     * @param string|null $hppRedirectUrl Ödənişdən sonra qayıdılan URL.
     * @param string|null $typeRid Sifariş növü.
     * @param list<string>|null $hppCofCapturePurposes Kartın saxlanma məqsədi.
     */
    public function __construct(
        public string $amount,
        public string $currency,
        public string $description,
        public array $aut,
        public ?string $language = null,
        #[Field(name: 'hppRedirectUrl')]
        public ?string $hppRedirectUrl = null,
        #[Field(name: 'typeRid')]
        public ?string $typeRid = null,
        #[Field(name: 'hppCofCapturePurposes')]
        public ?array $hppCofCapturePurposes = null,
    ) {
    }
}
