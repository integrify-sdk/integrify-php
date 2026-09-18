<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Kart qeydiyyatı **olmayan** `/api/order` sorğularının payload-u.
 *
 * | Metod | `typeRid` | `hppCofCapturePurposes` |
 * | :--- | :--- | :--- |
 * | `createOrder()` | `Order_SMS` | `['Cit']` | — |
 * | `saveCard()` | `Order_DMS` | `['Cit', 'Recurring']` | `{purpose: AddCard}` |
 * | `payAndSaveCard()` | `Order_SMS` | `['Cit']` | `{purpose: AddCard}` |
 * | `orderWithSavedCard()` | `Order_REC` | `null` | — |
 *
 * `amount` **sətirdir**, `float` deyil — səbəbi `KapitalClient::amount()`-da.
 */
final readonly class OrderRequest extends Data
{
    /**
     * @param string $amount Ödəniş məbləği, sətir formatında.
     * @param string $currency Məzənnə. Mümkün dəyərlər: `AZN`, `USD`.
     * @param string $description Ödənişin təsviri.
     * @param string|null $language Ödəniş səhifəsinin dili.
     * @param string|null $hppRedirectUrl Ödənişdən sonra qayıdılan URL.
     * @param string|null $typeRid Sifariş növü (`Order_SMS`, `Order_DMS`, `Order_REC`).
     * @param list<string>|null $hppCofCapturePurposes Kartın saxlanma məqsədi.
     */
    public function __construct(
        public string $amount,
        public string $currency,
        public string $description,
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
