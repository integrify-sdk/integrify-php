---
title: EPoint · Sorğu DTO-ları
---

# Sorğu DTO-ları

Bu obyektlər servisə **göndərilir**. `Məftildəki ad` sütunu payload-da hansı açarın görünəcəyini göstərir — PHP tərəfdəki ad heç vaxt məftilə çıxmır.

## CardRegistrationPaymentRequest

`payAndSaveCard()` sorğusunun payload-u.

`PaymentRequest`-dən yalnız bir şeylə fərqlənir: `other_attr` **yoxdur**. Bu
endpoint üçün EPoint onu qəbul etmir, və payload-dan `null` field-lər atılmadığı
üçün (bax: `EPointClient::payload()`) `PaymentRequest`-i təkrar istifadə etmək
məftilə `"other_attr": null` göndərərdi — Python kitabxanasının göndərmədiyi bir
field. `$description` isə burada məcburidir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Ödəniş məbləği, sətir formatında. |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. Mümkün dəyər: `AZN`. |
| `orderId` | `order_id` | `string` | ✅ | uzunluq 0–255 | Tətbiqinizdə unikal ID. |
| `description` | `description` | `string` | ✅ | uzunluq 0–1000 | Ödənişin təsviri. Bu endpoint üçün məcburidir. |
| `successRedirectUrl` | `success_redirect_url` | `string\|null` |  |  | Uğurlu ödənişdən sonra yönləndirilən URL. |
| `errorRedirectUrl` | `error_redirect_url` | `string\|null` |  |  | Uğursuz ödənişdən sonra yönləndirilən URL. |

## PaymentRequest

`pay()` və `payAndSaveCard()` sorğularının payload-u.

`amount` **sətirdir**, `float` deyil. EPoint məbləği JSON sətri kimi gözləyir
(Python kitabxanası `Decimal`-ı pydantic-in json rejimi ilə serialize edir və
nəticə `"100.50"` olur), və `float` yuvarlaqlaşma itkisi ilə `10.55` yerinə
`10.550000000000001` göndərə bilər. Klient metodları `int|float|string` qəbul
edib bura normallaşdırılmış sətir ötürür.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Ödəniş məbləği, sətir formatında (`'100'`, `'10.50'`). |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. Mümkün dəyər: `AZN`. |
| `orderId` | `order_id` | `string` | ✅ | uzunluq 0–255 | Tətbiqinizdə unikal ID. |
| `successRedirectUrl` | `success_redirect_url` | `string\|null` |  |  | Uğurlu ödənişdən sonra yönləndirilən URL. |
| `errorRedirectUrl` | `error_redirect_url` | `string\|null` |  |  | Uğursuz ödənişdən sonra yönləndirilən URL. |
| `description` | `description` | `string\|null` |  | uzunluq 0–1000 | Ödənişin təsviri. |
| `otherAttr` | `other_attr` | `?array` |  |  | Callback-də geri qaytarılan əlavə dəyərlər. |

## PayoutRequest

`payout()` sorğusunun payload-u — saxlanılmış karta pul köçürülməsi.

Endpoint `/api/1/refund-request` adlanır, lakin bu **refund deyil**: pul sizin
balansınızdan müştərinin kartına gedir. Ödənişin geri qaytarılması üçün
[`RefundRequest`](#refundrequest).

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Nağdlaşdırılacaq məbləğ, sətir formatında. |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. Mümkün dəyər: `AZN`. |
| `orderId` | `order_id` | `string` | ✅ | uzunluq 0–255 | Tətbiqinizdə unikal ID. |
| `cardId` | `card_id` | `string` | ✅ |  | Saxlanılmış kartın IDsi. |
| `description` | `description` | `string\|null` |  | uzunluq 0–1000 | Nağdlaşdırmanın təsviri. |

## RefundRequest

`refund()` sorğusunun payload-u.

`amount` verilməsə tam geri qaytarma, verilsə yarımçıq geri qaytarma olur.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `transaction` | `transaction` | `string` | ✅ |  | EPoint-in tranzaksiya IDsi. API-də field adı `transaction`-dır, Python kitabxanasında isə metod arqumenti `transaction_id` adlanır — burada da klient metodu `$transactionId` qəbul edir, məftildə `transaction` gedir. |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. Mümkün dəyər: `AZN`. |
| `amount` | `amount` | `string\|null` |  |  | Geri qaytarılacaq məbləğ, sətir formatında. `null` — tam geri qaytarma. |

## SavedCardPaymentRequest

`payWithSavedCard()` sorğusunun payload-u.

Yönləndirmə URL-ləri yoxdur: ödəniş server tərəfdə, müştəri iştirakı olmadan
icra olunur, ona görə yönləndiriləcək yer də yoxdur.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Ödəniş məbləği, sətir formatında. |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. Mümkün dəyər: `AZN`. |
| `orderId` | `order_id` | `string` | ✅ | uzunluq 0–255 | Tətbiqinizdə unikal ID. |
| `cardId` | `card_id` | `string` | ✅ |  | Saxlanılmış kartın IDsi. Adətən `ce` prefiksi ilə başlayır. |

## SplitCardRegistrationPaymentRequest

`splitPayAndSaveCard()` sorğusunun payload-u.

`SplitPaymentRequest`-dən `other_attr`-ın olmaması ilə fərqlənir — səbəbi
[`CardRegistrationPaymentRequest`](#cardregistrationpaymentrequest)-də izah olunub.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Ümumi ödəniş məbləği, sətir formatında. |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. Mümkün dəyər: `AZN`. |
| `orderId` | `order_id` | `string` | ✅ | uzunluq 0–255 | Tətbiqinizdə unikal ID. |
| `splitUser` | `split_user` | `string` | ✅ |  | Ödənişin bölündüyü EPoint istifadəçisinin IDsi. |
| `splitAmount` | `split_amount` | `string` | ✅ |  | Həmin istifadəçiyə gedən məbləğ, sətir formatında. |
| `successRedirectUrl` | `success_redirect_url` | `string\|null` |  |  | Uğurlu ödənişdən sonra yönləndirilən URL. |
| `errorRedirectUrl` | `error_redirect_url` | `string\|null` |  |  | Uğursuz ödənişdən sonra yönləndirilən URL. |
| `description` | `description` | `string\|null` |  | uzunluq 0–1000 | Ödənişin təsviri. |

## SplitPaymentRequest

`splitPay()` və `splitPayAndSaveCard()` sorğularının payload-u.

Bölünmüş ödənişdə məbləğin bir hissəsi ikinci **EPoint istifadəçisinə** gedir.
Məftildə field `split_user` adlanır, dəyər isə həmin istifadəçinin IDsidir —
ona görə klient metodu `$splitUserId` qəbul edir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Ümumi ödəniş məbləği, sətir formatında. |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. Mümkün dəyər: `AZN`. |
| `orderId` | `order_id` | `string` | ✅ | uzunluq 0–255 | Tətbiqinizdə unikal ID. |
| `splitUser` | `split_user` | `string` | ✅ |  | Ödənişin bölündüyü EPoint istifadəçisinin IDsi. |
| `splitAmount` | `split_amount` | `string` | ✅ |  | Həmin istifadəçiyə gedən məbləğ, sətir formatında. |
| `successRedirectUrl` | `success_redirect_url` | `string\|null` |  |  | Uğurlu ödənişdən sonra yönləndirilən URL. |
| `errorRedirectUrl` | `error_redirect_url` | `string\|null` |  |  | Uğursuz ödənişdən sonra yönləndirilən URL. |
| `description` | `description` | `string\|null` |  | uzunluq 0–1000 | Ödənişin təsviri. |
| `otherAttr` | `other_attr` | `?array` |  |  | Callback-də geri qaytarılan əlavə dəyərlər. |

## SplitSavedCardPaymentRequest

`splitPayWithSavedCard()` sorğusunun payload-u.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `amount` | `amount` | `string` | ✅ |  | Ümumi ödəniş məbləği, sətir formatında. |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. Mümkün dəyər: `AZN`. |
| `orderId` | `order_id` | `string` | ✅ | uzunluq 0–255 | Tətbiqinizdə unikal ID. |
| `cardId` | `card_id` | `string` | ✅ |  | Saxlanılmış kartın IDsi. |
| `splitUser` | `split_user` | `string` | ✅ |  | Ödənişin bölündüyü EPoint istifadəçisinin IDsi. |
| `splitAmount` | `split_amount` | `string` | ✅ |  | Həmin istifadəçiyə gedən məbləğ, sətir formatında. |
| `description` | `description` | `string\|null` |  | uzunluq 0–1000 | Ödənişin təsviri. |

## TransactionStatusRequest

`getTransactionStatus()` sorğusunun payload-u.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `transaction` | `transaction` | `string` | ✅ |  | EPoint-in tranzaksiya IDsi. Adətən `te` prefiksi ilə olur. |
