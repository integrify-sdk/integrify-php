# EPointClient

EPoint ödəniş şlüzü (`https://epoint.az`).

```php
$client = new EPointClient(EPointConfig::fromEnvironment());

$result = $client->pay(amount: 100, currency: 'AZN', orderId: 'order-1');

$result->isSuccessful();   // bool
$result->redirectUrl;      // müştərini bura yönləndirin
```

**EPoint həmişə HTTP 200 qaytarır**, ödəniş rədd olunanda da. Yəni `RequestFailed`
atılmaması "ödəniş alındı" demək deyil — hər cavab DTO-sunda `isSuccessful()` var
və ona baxmaq məcburidir. `RequestFailed` yalnız şəbəkə səviyyəsindəki problem
(və ya şlüzün 5xx səhifəsi) üçün atılır.

Hər sorğunun body-si eyni **zərfdir**: payload JSON-a çevrilib base64-lənir və
`EPOINT_PRIVATE_KEY` ilə imzalanır, məftildə isə yalnız `{data, signature}` gedir.
Private key heç vaxt göndərilmir.

## `pay()`

Ödəniş başladır.

**POST** `/api/1/request`

Cavabdaki `redirectUrl` ödənişin **başlanğıcıdır**: müştəri həmin səhifədə kart
məlumatlarını yazır. Nəticə isə callback URL-inizə gəlir və `Callback::decode()`
ilə açılır — eyni `orderId` ilə.

```php
pay(int|float|string $amount, string $currency, string $orderId, string|null $description, ?array $otherAttr): RedirectUrl
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `int\|float\|string` | ✅ | Ödəniş məbləği. Pul üçün `string` və ya `int` tövsiyə olunur; `float`-un dəqiqlik itkisi barədə `amount()`-a baxın. |
| `$currency` | `string` | ✅ | Məzənnə. Mümkün dəyər: `AZN`. |
| `$orderId` | `string` | ✅ | Tətbiqinizdə unikal ID. Maksimum 255 simvol. |
| `$description` | `string\|null` |  | Ödənişin təsviri. Maksimum 1000 simvol. |
| `$otherAttr` | `?array` |  | Callback-də sizə geri qaytarılan əlavə dəyərlər. |

**Atır:**

- `InvalidRequest` — `$amount` ədədi dəyər deyilsə.
- `RequestFailed`

## `getTransactionStatus()`

Tranzaksiyanın statusunu soruşur.

**POST** `/api/1/get-status`

Diqqət: bu cavabın `isSuccessful()`-u **sorğunun** alındığını bildirir, ödənişin
uğurlu olduğunu deyil. Ödəniş üçün `isPaid()`.

```php
getTransactionStatus(string $transactionId): TransactionStatusResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$transactionId` | `string` | ✅ | EPoint-in tranzaksiya IDsi. Adətən `te` prefiksi ilə. |

**Atır:**

- `RequestFailed`

## `saveCard()`

Ödəniş olmadan kartı yadda saxlayır.

**POST** `/api/1/card-registration`

Cavabda `cardId` gəlir, lakin o **hələ istifadəyə hazır deyil**: müştəri
`redirectUrl`-də kartı uğurla qeyd etməyincə onunla ödəniş etmək mümkün deyil.
Təsdiq callback ilə, eyni `cardId` ilə gəlir.

```php
saveCard(): RedirectUrlWithCardId
```

**Atır:**

- `RequestFailed`

## `payWithSavedCard()`

Yadda saxlanılmış kartla ödəniş edir.

**POST** `/api/1/execute-pay`

Müştəri iştirakı olmadan, server tərəfdə icra olunur — ona görə yönləndirmə
URL-i yoxdur, nəticə dərhal cavabda gəlir.

```php
payWithSavedCard(string|int|float $amount, string $currency, string $orderId, string $cardId): PaymentResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$orderId` | `string` | ✅ |  |
| `$cardId` | `string` | ✅ | Saxlanılmış kartın IDsi. Adətən `ce` prefiksi ilə başlayır. |

**Atır:**

- `InvalidRequest`
- `RequestFailed`

## `payAndSaveCard()`

Ödəniş edir və kartı yadda saxlayır.

**POST** `/api/1/card-registration-with-pay`

`$description` burada **məcburidir** — EPoint bu endpoint üçün onu tələb edir,
halbuki `pay()` üçün etmir.

```php
payAndSaveCard(string|int|float $amount, string $currency, string $orderId, string $description): RedirectUrlWithCardId
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$orderId` | `string` | ✅ |  |
| `$description` | `string` | ✅ |  |

**Atır:**

- `InvalidRequest`
- `RequestFailed`

## `payout()`

Saxlanılmış karta pul köçürür (payout).

**POST** `/api/1/refund-request`

Endpoint-in adına baxmayaraq bu **refund deyil**: pul sizin balansınızdan
müştərinin kartına gedir. Ödənişi geri qaytarmaq üçün `refund()`.

```php
payout(string|int|float $amount, string $currency, string $orderId, string $cardId, ?string $description): PaymentResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$orderId` | `string` | ✅ |  |
| `$cardId` | `string` | ✅ |  |
| `$description` | `?string` |  |  |

**Atır:**

- `InvalidRequest`
- `RequestFailed`

## `refund()`

Ödənişi geri qaytarır.

**POST** `/api/1/reverse`

`$amount` verilməsə tam, verilsə yarımçıq geri qaytarma olur.

```php
refund(string $transactionId, string $currency, int|float|string|null $amount): MinimalResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$transactionId` | `string` | ✅ | Geri qaytarılacaq tranzaksiyanın IDsi. |
| `$currency` | `string` | ✅ |  |
| `$amount` | `int\|float\|string\|null` |  | Geri qaytarılacaq məbləğ, və ya `null` — hamısı. |

**Atır:**

- `InvalidRequest`
- `RequestFailed`

## `splitPay()`

Bölünmüş ödəniş başladır.

**POST** `/api/1/split-request`

```php
splitPay(string|int|float $amount, string $currency, string $orderId, string $splitUserId, int|float|string $splitAmount, ?string $description, ?array $otherAttr): RedirectUrl
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$orderId` | `string` | ✅ |  |
| `$splitUserId` | `string` | ✅ | Ödənişin bölündüyü **EPoint istifadəçisinin** IDsi. |
| `$splitAmount` | `int\|float\|string` | ✅ | Həmin istifadəçiyə gedən məbləğ. |
| `$description` | `?string` |  |  |
| `$otherAttr` | `?array` |  | Callback-də geri qaytarılan əlavə dəyərlər. |

**Atır:**

- `InvalidRequest`
- `RequestFailed`

## `splitPayWithSavedCard()`

Saxlanılmış kartla bölünmüş ödəniş edir.

**POST** `/api/1/split-execute-pay`

```php
splitPayWithSavedCard(string|int|float $amount, string $currency, string $orderId, string $cardId, string $splitUserId, string|int|float $splitAmount, ?string $description): SplitPaymentResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$orderId` | `string` | ✅ |  |
| `$cardId` | `string` | ✅ |  |
| `$splitUserId` | `string` | ✅ |  |
| `$splitAmount` | `string\|int\|float` | ✅ |  |
| `$description` | `?string` |  |  |

**Atır:**

- `InvalidRequest`
- `RequestFailed`

## `splitPayAndSaveCard()`

Bölünmüş ödəniş edir və kartı yadda saxlayır.

**POST** `/api/1/split-card-registration-with-pay`

```php
splitPayAndSaveCard(string|int|float $amount, string $currency, string $orderId, string $splitUserId, string|int|float $splitAmount, ?string $description): RedirectUrlWithCardId
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$orderId` | `string` | ✅ |  |
| `$splitUserId` | `string` | ✅ |  |
| `$splitAmount` | `string\|int\|float` | ✅ |  |
| `$description` | `?string` |  |  |

**Atır:**

- `InvalidRequest`
- `RequestFailed`
