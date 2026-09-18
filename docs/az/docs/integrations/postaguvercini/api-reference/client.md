---
title: Posta Güvercini · Klient
---

# PostaGuverciniClient

Posta Güvercini SMS servisi (`https://www.poctgoyercini.com`).

```php
$client = new PostaGuverciniClient(PostaGuverciniConfig::fromEnvironment());

$result = $client->sendSms('Salam!', ['994501234567']);

$result->isSuccessful();   // bool
$result->messageIds();     // status sorğusu üçün
```

**Servis uğursuz sorğuya da HTTP 200 qaytarır** — nəticə body-dəki `StatusCode`
field-indədir. Yəni `RequestFailed` atılmaması "göndərildi" demək deyil; hər cavab
DTO-sunda `isSuccessful()` var və ona baxmaq məcburidir.

Bütün sorğular `POST`-dur və hamısı hesabın adını və **parolunu payload-da**
daşıyır — bax: [`PostaGuverciniConfig`](config.md#postaguverciniconfig).

## `sendSms()`

Bir mətni bir və ya bir neçə nömrəyə göndərir.

**POST** `/api_json/v1/Sms/Send_1_N`

```php
sendSms(string $message, list<string> $receivers, DateTimeInterface|string|null $sendDate, DateTimeInterface|string|null $expireDate, Channel $channel, string|null $originator): SendResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$message` | `string` | ✅ | Göndəriləcək mətn. |
| `$receivers` | `list<string>` | ✅ | Alıcı nömrələri (`994501234567`). |
| `$sendDate` | `DateTimeInterface\|string\|null` |  | Göndərilmə vaxtı; `null` — indi. Sətir veriləcəksə formatı `YYYYMMDD HH:MM` olmalıdır. |
| `$expireDate` | `DateTimeInterface\|string\|null` |  | Etibarlılıq müddəti. |
| `$channel` | `Channel` |  | Kanal; default `OTP`. |
| `$originator` | `string\|null` |  | Göndərən adı; verilməsə konfiqurasiyadakı işlənir. |

**Atır:**

- `InvalidRequest` — Alıcı siyahısı boşdursa, və ya tarix formatı yanlışdırsa.
- `RequestFailed`

## `sendMessages()`

Hər nömrəyə **öz** mətnini göndərir.

**POST** `/api_json/v1/Sms/Send_N_N`

```php
$client->sendMessages([
    new SmsMessage('994501234567', 'Salam, Əli'),
    new SmsMessage('994551234567', 'Salam, Aysel'),
]);
```

Nömrə və mətn bir DTO-da saxlanılır, iki paralel siyahı kimi yox: fərqli
uzunluqlu siyahılar səssizcə qısalır və bir mesaj heç kimə getmir.

```php
sendMessages(list<SmsMessage> $messages, DateTimeInterface|string|null $sendDate, DateTimeInterface|string|null $expireDate, Channel $channel, ?string $originator): SendResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$messages` | `list<SmsMessage>` | ✅ | Nömrə–mətn cütləri. |
| `$sendDate` | `DateTimeInterface\|string\|null` |  |  |
| `$expireDate` | `DateTimeInterface\|string\|null` |  |  |
| `$channel` | `Channel` |  |  |
| `$originator` | `?string` |  |  |

**Atır:**

- `InvalidRequest` — Siyahı boşdursa, və ya tarix formatı yanlışdırsa.
- `RequestFailed`

## `getStatus()`

Göndərilmiş mesajların çatdırılma vəziyyətini soruşur.

**POST** `/api_json/v1/Sms/Status`

```php
getStatus(list<string> $messageIds): StatusResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$messageIds` | `list<string>` | ✅ | Göndərmə cavabındakı `messageIds()` dəyərləri. |

**Atır:**

- `InvalidRequest` — Siyahı boşdursa.
- `RequestFailed`

## `checkBalance()`

Hesabdakı qalan krediti qaytarır.

**POST** `/api_json/v1/Sms/CreditBalance`

```php
checkBalance(): BalanceResult
```

**Atır:**

- `RequestFailed`
