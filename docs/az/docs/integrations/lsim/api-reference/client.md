# BulkSmsClient

Toplu SMS göndərilməsi (`https://www.sendsms.az`).

Tək SMS API-sindən tamamilə ayrıdır: başqa host, tək endpoint, və hər sorğuda
nə edildiyini `operation` field-i bildirir. Payload iç-içə strukturdadır:

```json
{"request": {"head": { ... }, "body": [ ... ]}}
```

```php
$client = new BulkSmsClient(LsimConfig::fromEnvironment());

$submission = $client->sendSameMessage(1, ['994501234567', '994551234567'], 'Salam!');
$report = $client->fetchReport($submission->taskId ?? 0);
```

## `sendSameMessage()`

Eyni mesajı bir neçə nömrəyə göndərir.

**POST** `/smxml/api` (`operation: submit`, `isbulk: true`)

```php
sendSameMessage(int $controlId, list<string> $msisdns, string $message, DateTimeInterface|null $at, ?string $title): BulkSubmission
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$controlId` | `int` | ✅ | Sizin təyin etdiyiniz təkrarlanmayan id. LSIM eyni id ilə ikinci sorğunu `Duplicate` kimi rədd edir. |
| `$msisdns` | `list<string>` | ✅ | Nömrələr. |
| `$message` | `string` | ✅ | Hamısına gedəcək mesaj. |
| `$at` | `DateTimeInterface\|null` |  | Planlaşdırılmış göndərilmə zamanı. |
| `$title` | `?string` |  |  |

**Atır:**

- `InvalidRequest` — Nömrə siyahısı boşdursa.
- `RequestFailed`

## `sendDifferentMessages()`

Hər nömrəyə öz mesajını göndərir.

**POST** `/smxml/api` (`operation: submit`, `isbulk: false`)

```php
sendDifferentMessages(int $controlId, list<string> $msisdns, list<string> $messages, ?DateTimeInterface $at, ?string $title): BulkSubmission
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$controlId` | `int` | ✅ |  |
| `$msisdns` | `list<string>` | ✅ | Nömrələr. |
| `$messages` | `list<string>` | ✅ | Nömrələrlə **eyni sırada** mesajlar. |
| `$at` | `?DateTimeInterface` |  |  |
| `$title` | `?string` |  |  |

**Atır:**

- `InvalidRequest` — Siyahılar boşdursa və ya uzunluqları fərqlidirsə.
- `RequestFailed`

## `fetchReport()`

Tapşırığın yekun hesabatı — hər statusdan neçə SMS olduğu.

**POST** `/smxml/api` (`operation: report`)

```php
fetchReport(int $taskId): BulkReport
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$taskId` | `int` | ✅ |  |

**Atır:**

- `RequestFailed`

## `fetchDetailedReport()`

Tapşırığın detallı hesabatı — hər SMS üçün bir sətir.

**POST** `/smxml/api` (`operation: detailedreport`)

```php
fetchDetailedReport(int $taskId): BulkDetailedReport
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$taskId` | `int` | ✅ |  |

**Atır:**

- `RequestFailed`

## `fetchDetailedReportWithDates()`

Detallı hesabat, hər sətirdə çatdırılma tarixi ilə.

**POST** `/smxml/api` (`operation: detailedreportwithdate`)

```php
fetchDetailedReportWithDates(int $taskId): BulkDetailedReport
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$taskId` | `int` | ✅ |  |

**Atır:**

- `RequestFailed`

## `checkBalance()`

Toplu SMS hesabının balansı.

**POST** `/smxml/api` (`operation: units`)

```php
checkBalance(): BulkBalance
```

**Atır:**

- `RequestFailed`

# SingleSmsClient

Tək SMS göndərilməsi (`https://apps.lsim.az`).

Toplu göndərilmə ayrı API-dir, ayrı host-dadır — ona görə ayrı klientdir:
[`BulkSmsClient`](#bulksmsclient).

```php
$client = new SingleSmsClient(LsimConfig::fromEnvironment());

$result = $client->sendSms('994501234567', 'Salam!');

$result->isSuccessful();  // bool
$result->obj;             // transaction id
```

## `sendSms()`

SMS göndərir.

**GET** `/quicksms/v1/send`

```php
sendSms(string $msisdn, string $text, bool $unicode, string|null $sender): SmsResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$msisdn` | `string` | ✅ | Nömrə: ölkə kodu + operator kodu + nömrə (`994501234567`). |
| `$text` | `string` | ✅ | Mesajın məzmunu. |
| `$unicode` | `bool` |  | Mesajda `ə`, `ş`, `ü` kimi simvollar varsa `true` verin. |
| `$sender` | `string\|null` |  | Göndərən adı; verilməsə konfiqurasiyadakı istifadə olunur. |

**Atır:**

- `RequestFailed`

## `scheduleSms()`

SMS-i göndərir və ya gələcək bir zamana planlaşdırır.

**POST** `/quicksms/v1/smssender`

```php
scheduleSms(string $msisdn, string $text, DateTimeInterface|null $at, bool $unicode, ?string $sender): SmsPostResult
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$msisdn` | `string` | ✅ |  |
| `$text` | `string` | ✅ |  |
| `$at` | `DateTimeInterface\|null` |  | Göndərilmə zamanı. `null` — indi. |
| `$unicode` | `bool` |  |  |
| `$sender` | `?string` |  |  |

**Atır:**

- `RequestFailed`

## `checkBalance()`

Qalan SMS sayını qaytarır — `obj` field-ində.

**GET** `/quicksms/v1/balance`

```php
checkBalance(): SmsResult
```

**Atır:**

- `RequestFailed`

## `checkStatus()`

Göndərilmiş SMS-in status kodunu qaytarır.

**GET** `/quicksms/v1/report`

Bu endpoint JSON deyil, sadəcə bir ədəd qaytarır (məs., `101`), ona görə cavab
DTO-ya hidrasiya olunmur — `ReportStatus::fromBody()` xam body-ni oxuyur.

Hesabat yalnız son bir həftə üçün əlçatandır.

```php
checkStatus(int $transactionId): ReportStatus
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$transactionId` | `int` | ✅ | Uğurlu göndərilmədə alınan `obj` dəyəri. |

**Atır:**

- `RequestFailed`

## `fetchDeliveryReport()`

Göndərilmiş SMS-in mətnli çatdırılma hesabatı.

**POST** `/quicksms/v1/smsreporter`

```php
fetchDeliveryReport(int $transactionId): DeliveryReport
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$transactionId` | `int` | ✅ | Uğurlu göndərilmədə alınan `obj` dəyəri. |

**Atır:**

- `RequestFailed`
