---
title: Azericard · Klient
---

# AzericardClient

Azericard — kart ödənişləri (MPI) və pul köçürmələri (MT).

**Bu klientin əksər metodu HTTP sorğusu atmır.** Azericard-ın kart əməliyyatları
server-server deyil: siz müştərinin brauzerinə bir forma verirsiniz, brauzer onu
Azericard-a göndərir, nəticə isə sizin callback URL-inizə gəlir.

| Metod | Nə edir |
| :--- | :--- |
| `authorize()` | forma qaytarır — HTTP yoxdur |
| `authorizeAndSaveCard()` | forma qaytarır — HTTP yoxdur |
| `authorizeWithSavedCard()` | forma qaytarır — HTTP yoxdur |
| `finalize()` | forma qaytarır — HTTP yoxdur |
| `startTransfer()` | forma qaytarır — HTTP yoxdur |
| `getTransactionStatus()` | **POST** edir |
| `confirmTransfer()` | **POST** edir |
| `declineTransfer()` | **POST** edir |

```php
$client = new AzericardClient(AzericardConfig::fromEnvironment());

$form = $client->authorize(amount: 10.5, currency: '944', order: '123456', description: 'Sifariş');

echo $form->toHtml();   // brauzer bunu Azericard-a göndərir
```

Nəticə `AZERICARD_CALLBACK_URL`-ə post olunur və `Callback::decodeAuth()` ilə oxunur.

## `authorize()`

Ödəniş üçün forma qurur. **HTTP sorğusu atmır.**

```php
authorize(int|float|string $amount, string $currency, string $order, string $description, AuthorizationType $type, DateTimeInterface|null $timestamp, string|null $nonce, ?array $browserInfo, ?string $name, ?string $country, ?string $merchantGmt): FormPost
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `int\|float\|string` | ✅ | Ödəniş məbləği. |
| `$currency` | `string` | ✅ | 3 simvollu valyuta kodu (AZN üçün `944`). |
| `$order` | `string` | ✅ | Unikal sifariş IDsi, 6–32 rəqəm. |
| `$description` | `string` | ✅ | Ödənişin təsviri, maksimum 50 simvol. |
| `$type` | `AuthorizationType` |  | `Direct` — məbləği sil, `Freeze` — blokla. |
| `$timestamp` | `DateTimeInterface\|null` |  | Sorğunun vaxtı; `null` — indi (UTC). |
| `$nonce` | `string\|null` |  | Təsadüfi dəyər; `null` — avtomatik. |
| `$browserInfo` | `?array` |  | `M_INFO` — brauzer məlumatları. |
| `$name` | `?string` |  |  |
| `$country` | `?string` |  |  |
| `$merchantGmt` | `?string` |  |  |

**Atır:**

- `InvalidRequest`
- `MissingConfiguration` — Açar və ya merchant məlumatları yoxdursa.

## `authorizeAndSaveCard()`

Ödəniş edir və kartı yadda saxlayır. **HTTP sorğusu atmır.**

Payload-a `TOKEN_ACTION=REGISTER` əlavə olunur; token sonra callback-də gəlir.

```php
authorizeAndSaveCard(string|int|float $amount, string $currency, string $order, string $description, AuthorizationType $type, ?DateTimeInterface $timestamp, ?string $nonce, ?array $browserInfo, ?string $name, ?string $country, ?string $merchantGmt): FormPost
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$order` | `string` | ✅ |  |
| `$description` | `string` | ✅ |  |
| `$type` | `AuthorizationType` |  |  |
| `$timestamp` | `?DateTimeInterface` |  |  |
| `$nonce` | `?string` |  |  |
| `$browserInfo` | `?array` |  | `M_INFO` — brauzer məlumatları. |
| `$name` | `?string` |  |  |
| `$country` | `?string` |  |  |
| `$merchantGmt` | `?string` |  |  |

**Atır:**

- `InvalidRequest`
- `MissingConfiguration`

## `authorizeWithSavedCard()`

Saxlanılmış kartla ödəniş forması. **HTTP sorğusu atmır.**

```php
authorizeWithSavedCard(string|int|float $amount, string $currency, string $order, string $description, string $token, AuthorizationType $type, ?DateTimeInterface $timestamp, ?string $nonce, ?array $browserInfo, ?string $name, ?string $country, ?string $merchantGmt): FormPost
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$order` | `string` | ✅ |  |
| `$description` | `string` | ✅ |  |
| `$token` | `string` | ✅ | Callback-də gələn 28 simvollu kart tokeni. |
| `$type` | `AuthorizationType` |  |  |
| `$timestamp` | `?DateTimeInterface` |  |  |
| `$nonce` | `?string` |  |  |
| `$browserInfo` | `?array` |  | `M_INFO` — brauzer məlumatları. |
| `$name` | `?string` |  |  |
| `$country` | `?string` |  |  |
| `$merchantGmt` | `?string` |  |  |

**Atır:**

- `InvalidRequest`
- `MissingConfiguration`

## `finalize()`

Bloklanmış məbləği tamamlayır, qaytarır və ya azad edir. **HTTP sorğusu atmır.**

`authorize()` `Freeze` növü ilə çağırılıbsa, bu addım məcburidir.

```php
finalize(string|int|float $amount, string $currency, string $order, string $rrn, string $internalReference, ResponseType $type, ?DateTimeInterface $timestamp, ?string $nonce): FormPost
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$order` | `string` | ✅ |  |
| `$rrn` | `string` | ✅ | Callback-də gələn 12 simvollu RRN. |
| `$internalReference` | `string` | ✅ | Callback-də gələn `INT_REF`. |
| `$type` | `ResponseType` |  | `AcceptPayment` — sil, `ReturnPayment` — geri qaytar, `CancelPayment` — bloku azad et. |
| `$timestamp` | `?DateTimeInterface` |  |  |
| `$nonce` | `?string` |  |  |

**Atır:**

- `InvalidRequest`
- `MissingConfiguration`

## `getTransactionStatus()`

Tranzaksiyanın vəziyyətini soruşur. **Bu metod POST edir.**

```php
getTransactionStatus(AuthorizationType|ResponseType $originalType, string $order, ?DateTimeInterface $timestamp, ?string $nonce): TransactionStatus
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$originalType` | `AuthorizationType\|ResponseType` | ✅ | Soruşulan əməliyyatın növü. |
| `$order` | `string` | ✅ |  |
| `$timestamp` | `?DateTimeInterface` |  |  |
| `$nonce` | `?string` |  |  |

**Atır:**

- `MissingConfiguration`
- `RequestFailed`

## `startTransfer()`

Pul köçürməsi üçün forma qurur. **HTTP sorğusu atmır.**

MT sistemidir, ona görə MD5 açarı istifadə olunur — kart əməliyyatlarının RSA
açarı deyil.

```php
startTransfer(string $merchant, string $srn, string|int|float $amount, string $currency, string $receiverCredentials, string $redirectLink): FormPost
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$merchant` | `string` | ✅ | Şirkət adı. |
| `$srn` | `string` | ✅ | Sizin unikal əməliyyat nömrəniz. |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ | 3 rəqəmli valyuta kodu (AZN üçün `944`). |
| `$receiverCredentials` | `string` | ✅ | Alıcının tam adı. |
| `$redirectLink` | `string` | ✅ | Əməliyyatın sonunda yönləndiriləcək ünvan. |

**Atır:**

- `InvalidRequest`
- `MissingConfiguration`

## `confirmTransfer()`

Gözləyən köçürməni təsdiqləyir. **Bu metod POST edir.**

```php
confirmTransfer(string $merchant, string $srn, string|int|float $amount, string $currency, ?DateTimeInterface $timestamp): TransferResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$merchant` | `string` | ✅ |  |
| `$srn` | `string` | ✅ |  |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$timestamp` | `?DateTimeInterface` |  |  |

**Atır:**

- `InvalidRequest`
- `MissingConfiguration`
- `RequestFailed`

## `declineTransfer()`

Gözləyən köçürməni rədd edir. **Bu metod POST edir.**

```php
declineTransfer(string $merchant, string $srn, string|int|float $amount, string $currency, ?DateTimeInterface $timestamp): TransferResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$merchant` | `string` | ✅ |  |
| `$srn` | `string` | ✅ |  |
| `$amount` | `string\|int\|float` | ✅ |  |
| `$currency` | `string` | ✅ |  |
| `$timestamp` | `?DateTimeInterface` |  |  |

**Atır:**

- `InvalidRequest`
- `MissingConfiguration`
- `RequestFailed`
