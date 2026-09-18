# Sorğu DTO-ları

Bu obyektlər servisə **göndərilir**. `Məftildəki ad` sütunu payload-da hansı açarın görünəcəyini göstərir — PHP tərəfdəki ad heç vaxt məftilə çıxmır.

## CardRegistrationOrderRequest

Kartı da qeyd edən `/api/order` sorğularının payload-u.

| Metod | `typeRid` | `hppCofCapturePurposes` | `aut` |
| :--- | :--- | :--- | :--- |
| `saveCard()` | `Order_DMS` | `['Cit', 'Recurring']` | `{purpose: AddCard}` |
| `payAndSaveCard()` | `Order_SMS` | `['Cit']` | `{purpose: AddCard}` |

[`OrderRequest`](#orderrequest)-dən yalnız `aut` field-i ilə fərqlənir. Ayrı
DTO olmasının səbəbi budur: `null` field-lər payload-dan atılmır (bax:
`KapitalClient::order()`), ona görə `aut`-u ortaq DTO-ya qoymaq `createOrder()`
sorğusuna Python-un göndərmədiyi `"aut": null` açarını əlavə edərdi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Ödəniş məbləği, sətir formatında. |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. Mümkün dəyərlər: `AZN`, `USD`. |
| `description` | `description` | `string` | ✅ |  | Ödənişin təsviri. |
| `aut` | `aut` | `array` | ✅ |  | Kart qeydiyyatının məqsədi. |
| `language` | `language` | `string\|null` |  |  | Ödəniş səhifəsinin dili. |
| `hppRedirectUrl` | `hppRedirectUrl` | `string\|null` |  |  | Ödənişdən sonra qayıdılan URL. |
| `typeRid` | `typeRid` | `string\|null` |  |  | Sifariş növü. |
| `hppCofCapturePurposes` | `hppCofCapturePurposes` | `list<string>\|null` |  |  | Kartın saxlanma məqsədi. |

## ClearingRequest

`clearingOrder()` tranzaksiyasının payload-u — avtorizasiya olunmuş məbləğin
faktiki silinməsi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Silinəcək məbləğ, sətir formatında. |
| `phase` | `phase` | `string` |  |  | Tranzaksiyanın mərhələsi. |

## FullReverseRequest

`fullReverseOrder()` tranzaksiyasının payload-u — avtorizasiyanın tam ləğvi.

Məbləğ yoxdur: tam ləğv bütün avtorizasiya olunmuş məbləği qaytarır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `phase` | `phase` | `string` |  |  | Tranzaksiyanın mərhələsi. |
| `voidKind` | `voidKind` | `string` |  |  | Ləğvin növü. |

## OrderRequest

Kart qeydiyyatı **olmayan** `/api/order` sorğularının payload-u.

| Metod | `typeRid` | `hppCofCapturePurposes` |
| :--- | :--- | :--- |
| `createOrder()` | `Order_SMS` | `['Cit']` | — |
| `saveCard()` | `Order_DMS` | `['Cit', 'Recurring']` | `{purpose: AddCard}` |
| `payAndSaveCard()` | `Order_SMS` | `['Cit']` | `{purpose: AddCard}` |
| `orderWithSavedCard()` | `Order_REC` | `null` | — |

`amount` **sətirdir**, `float` deyil — səbəbi `KapitalClient::amount()`-da.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Ödəniş məbləği, sətir formatında. |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. Mümkün dəyərlər: `AZN`, `USD`. |
| `description` | `description` | `string` | ✅ |  | Ödənişin təsviri. |
| `language` | `language` | `string\|null` |  |  | Ödəniş səhifəsinin dili. |
| `hppRedirectUrl` | `hppRedirectUrl` | `string\|null` |  |  | Ödənişdən sonra qayıdılan URL. |
| `typeRid` | `typeRid` | `string\|null` |  |  | Sifariş növü (`Order_SMS`, `Order_DMS`, `Order_REC`). |
| `hppCofCapturePurposes` | `hppCofCapturePurposes` | `list<string>\|null` |  |  | Kartın saxlanma məqsədi. |

## PartialReverseRequest

`partialReverseOrder()` tranzaksiyasının payload-u — avtorizasiyanın bir hissəsinin
ləğvi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Ləğv ediləcək məbləğ, sətir formatında. |
| `phase` | `phase` | `string` |  |  | Tranzaksiyanın mərhələsi. |
| `voidKind` | `voidKind` | `string` |  |  | Ləğvin növü. |

## RefundRequest

`refundOrder()` tranzaksiyasının payload-u.

Diqqət: bu DTO-nun field adları **camelCase deyil**. Python-da `/api/order` sxemləri
`to_camel` alias generator-undan istifadə edir, `exec-tran` sxemləri isə etmir —
ona görə burada `phase` və `type` olduğu kimi gedir. Uyğunsuzluq bankın API-sindədir,
bizim tərəfdə deyil.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Geri qaytarılacaq məbləğ, sətir formatında. |
| `phase` | `phase` | `string` |  |  | Tranzaksiyanın mərhələsi. |
| `type` | `type` | `string` |  |  | Tranzaksiyanın növü. |

## SavedCardPaymentRequest

`processPaymentWithSavedCard()` tranzaksiyasının payload-u.

`conditions.cofUsage = 'Cit'` — Customer Initiated Transaction, yəni müştəri
ödənişi özü başladır (abunə kimi avtomatik yox).

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Ödəniş məbləği, sətir formatında. |
| `phase` | `phase` | `string\|null` |  |  | Tranzaksiyanın mərhələsi. |
| `conditions` | `conditions` | `?array` |  |  | Kartın istifadə şərtləri. |
