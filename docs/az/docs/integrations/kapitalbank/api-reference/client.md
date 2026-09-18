---
title: Kapital Bank · Klient
---

# KapitalClient

Kapital Bank e-commerce şlüzü.

```php
$client = new KapitalClient(KapitalConfig::fromEnvironment());

$order = $client->createOrder(amount: 10.5, currency: 'AZN', description: 'Sifariş');

$order->id;              // sifariş IDsi
$order->redirectUrl();   // müştərini bura yönləndirin
```

**Xətalar exception-dur.** EPoint-dən fərqli olaraq Kapital Bank uğursuz sorğuya
400-dən böyük status qaytarır, ona görə `isSuccessful()` kimi metod yoxdur —
uğursuzluq `RequestRejected` kimi qalxır və bankın `errorCode`-unu daşıyır.

Hər sorğu **zərflə** gedir: `/api/order` payload-ları `{"order": {...}}`,
`exec-tran` payload-ları isə `{"tran": {...}}` içindədir; cavab da eyni açardan
oxunur.

## `createOrder()`

Adi ödəniş sifarişi yaradır.

**POST** `/api/order`

Cavabdakı `redirectUrl()` ödənişin **başlanğıcıdır**: müştəri həmin səhifədə
kart məlumatlarını yazır.

```php
createOrder(int|float|string $amount, string $currency, string $description): CreatedOrder
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `int\|float\|string` | ✅ | Ödəniş məbləği. Pul üçün `string` və ya `int` tövsiyə olunur; `float`-un dəqiqlik itkisi barədə `amount()`-a baxın. |
| `$currency` | `string` | ✅ | Məzənnə. Mümkün dəyərlər: `AZN`, `USD`. |
| `$description` | `string` | ✅ | Ödənişin təsviri. |

**Atır:**

- `InvalidRequest` — `$amount` ədədi dəyər deyilsə.
- `RequestRejected` — Bank sorğunu rədd etsə.
- `RequestFailed` — Şəbəkə xətası.

## `getOrderInformation()`

Sifarişin qısa vəziyyətini qaytarır.

**GET** `/api/order/{orderId}`

```php
getOrderInformation(int $orderId): OrderInformation
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$orderId` | `int` | ✅ |  |

**Atır:**

- `RequestRejected` — Sifariş tapılmasa.
- `RequestFailed`

## `getDetailedOrderInformation()`

Sifarişin tam vəziyyətini qaytarır — kart, 3-D Secure, cihaz və merchant daxil.

**GET** `/api/order/{orderId}`

Detal səviyyələri query parametri kimi gedir. Python kitabxanasında bunlar
endpoint sətrinin içindədir (`...?&tranDetailLevel=2...`, artıq `&` ilə);
burada `$query` parametridir, çünki `Client::uri()` path-də `?` görəndə
`InvalidRequest` atır — kodlanmamış istifadəçi dəyəri demək olar həmişə belə
görünür.

```php
getDetailedOrderInformation(int $orderId): DetailedOrderInformation
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$orderId` | `int` | ✅ |  |

**Atır:**

- `RequestRejected` — Sifariş tapılmasa.
- `RequestFailed`

## `refundOrder()`

Ödənişi geri qaytarır.

**POST** `/api/order/{orderId}/exec-tran`

```php
refundOrder(int $orderId, string|int|float $amount): TransactionResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$orderId` | `int` | ✅ |  |
| `$amount` | `string\|int\|float` | ✅ |  |

**Atır:**

- `InvalidRequest`
- `RequestRejected`
- `RequestFailed`

## `saveCard()`

Ödəniş olmadan kartı yadda saxlayır.

**POST** `/api/order`

`Order_DMS` növüdür: məbləğ kartda **bloklanır**, silinmir. Token müştəri
ödəniş səhifəsində kartı təsdiqlədikdən sonra
`getDetailedOrderInformation()->cardToken()` ilə oxunur.

```php
saveCard(string|int|float $amount, string $currency, string $description): CreatedOrder
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$description` | `string` | ✅ |  |

**Atır:**

- `InvalidRequest`
- `RequestRejected`
- `RequestFailed`

## `payAndSaveCard()`

Ödəniş edir və kartı yadda saxlayır.

**POST** `/api/order`

```php
payAndSaveCard(string|int|float $amount, string $currency, string $description): CreatedOrder
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$description` | `string` | ✅ |  |

**Atır:**

- `InvalidRequest`
- `RequestRejected`
- `RequestFailed`

## `fullReverseOrder()`

Avtorizasiyanı tam ləğv edir.

**POST** `/api/order/{orderId}/exec-tran`

Məbləğ qəbul etmir: tam ləğv bloklanmış məbləğin hamısını azad edir. Bir
hissəsini ləğv etmək üçün `partialReverseOrder()`.

```php
fullReverseOrder(int $orderId): TransactionResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$orderId` | `int` | ✅ |  |

**Atır:**

- `RequestRejected`
- `RequestFailed`

## `clearingOrder()`

Bloklanmış məbləği faktiki silir (clearing).

**POST** `/api/order/{orderId}/exec-tran`

`Order_DMS` axınının ikinci addımıdır: `saveCard()` məbləği bloklayır, clearing
onu hesabdan çıxarır.

```php
clearingOrder(int $orderId, string|int|float $amount): TransactionResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$orderId` | `int` | ✅ |  |
| `$amount` | `string\|int\|float` | ✅ |  |

**Atır:**

- `InvalidRequest`
- `RequestRejected`
- `RequestFailed`

## `partialReverseOrder()`

Avtorizasiyanın bir hissəsini ləğv edir.

**POST** `/api/order/{orderId}/exec-tran`

```php
partialReverseOrder(int $orderId, string|int|float $amount): TransactionResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$orderId` | `int` | ✅ |  |
| `$amount` | `string\|int\|float` | ✅ |  |

**Atır:**

- `InvalidRequest`
- `RequestRejected`
- `RequestFailed`

## `orderWithSavedCard()`

Saxlanılmış kartla ödəniş üçün sifariş yaradır.

**POST** `/api/order`

`Order_REC` növüdür və yönləndirmə URL-i göndərmir — ödəniş server tərəfdə
icra olunur. Tək başına kifayət etmir: ardınca `linkCardToken()` və
`processPaymentWithSavedCard()` gəlir. Üçünü birlikdə `payWithSavedCard()` edir.

```php
orderWithSavedCard(string|int|float $amount, string $currency, string $description): CreatedOrder
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$description` | `string` | ✅ |  |

**Atır:**

- `InvalidRequest`
- `RequestRejected`
- `RequestFailed`

## `linkCardToken()`

Saxlanılmış kart tokenini sifarişə bağlayır.

**POST** `/api/order/{orderId}/set-src-token`

```php
linkCardToken(int $token, int $orderId, string $password): LinkedCardToken
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$token` | `int` | ✅ |  |
| `$orderId` | `int` | ✅ |  |
| `$password` | `string` | ✅ | Sifarişin **öz** parolu (`CreatedOrder::$password`), merchant parolu deyil. |

**Atır:**

- `RequestRejected`
- `RequestFailed`

## `processPaymentWithSavedCard()`

Bağlanmış kartla ödənişi icra edir.

**POST** `/api/order/{orderId}/exec-tran`

```php
processPaymentWithSavedCard(string|int|float $amount, int $orderId, string $password): TransactionResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$orderId` | `int` | ✅ |  |
| `$password` | `string` | ✅ | Sifarişin öz parolu. |

**Atır:**

- `InvalidRequest`
- `RequestRejected`
- `RequestFailed`

## `payWithSavedCard()`

Saxlanılmış kartdan ödəniş — üç sorğunun birləşməsi.

Ardıcıllıq: `orderWithSavedCard()` → `linkCardToken()` →
`processPaymentWithSavedCard()`. Aralıq addımın nəticəsi lazımdırsa, metodları
ayrıca çağırın.

**Bu üç sorğu atomik deyil.** İkinci və ya üçüncü addım uğursuz olarsa,
yaradılmış sifariş bankda qalır — exception-ı tutub həmin sifarişi
`fullReverseOrder()` ilə ləğv etmək çağıran tərəfin işidir.

```php
payWithSavedCard(int $token, string|int|float $amount, string $currency, string $description): TransactionResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$token` | `int` | ✅ | Kartın tokeni (`DetailedOrderInformation::cardToken()`). |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$description` | `string` | ✅ |  |

**Atır:**

- `InvalidRequest`
- `RequestRejected`
- `RequestFailed`
