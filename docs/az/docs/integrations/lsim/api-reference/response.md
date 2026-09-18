---
title: LSIM · Cavab DTO-ları
---

# Cavab DTO-ları

Bu obyektlər servisdən **gəlir**. Enum-a bənzəyən field-lər sətir saxlanılır və enum ayrıca metodla verilir, ona görə servis yeni dəyər əlavə etdikdə cavab validasiyadan keçməkdə davam edir.

## BulkBalance

Toplu SMS hesabının balansı.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `responseCode` | `responseCode` | `int` | ✅ |  | Sorğunun nəticə kodu. Enum üçün `code()`. |
| `units` | `units` | `int` |  |  | Qalan SMS sayı. `-1` — LSIM dəyəri qaytarmadı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `code()` | `?BulkCode` |  |
| `isSuccessful()` | `bool` |  |

## BulkDetailedReport

Detallı hesabat: nəticə kodu + hər SMS üçün bir sətir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `responseCode` | `responseCode` | `int` | ✅ |  | Sorğunun nəticə kodu. Enum üçün `code()`. |
| `reports` | `reports` | `list<BulkSmsReport>` |  |  | Hər SMS üçün bir element. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `code()` | `?BulkCode` |  |
| `isSuccessful()` | `bool` |  |

## BulkReport

Toplu göndərilmənin yekun hesabatı — hər statusdan neçə SMS olduğu.

`-1` "LSIM bu sahəni qaytarmadı" deməkdir, sıfır yox.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `responseCode` | `responseCode` | `int` | ✅ |  |  |
| `expired` | `expired` | `int` |  |  |  |
| `removed` | `removed` | `int` |  |  |  |
| `blackList` | `blackList` | `int` |  |  |  |
| `undelivered` | `undelivered` | `int` |  |  |  |
| `delivered` | `delivered` | `int` |  |  |  |
| `duplicate` | `duplicate` | `int` |  |  |  |
| `error` | `error` | `int` |  |  |  |
| `send` | `send` | `int` |  |  |  |
| `queue` | `queue` | `int` |  |  |  |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `code()` | `?BulkCode` |  |
| `isSuccessful()` | `bool` |  |

## BulkSmsReport

Detallı hesabatda bir SMS-in vəziyyəti.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `msisdn` | `msisdn` | `string` | ✅ |  | Nömrə. LSIM onu rəqəm kimi qaytarır, lakin başında sıfır ola biləcəyi üçün burada sətir kimi saxlanılır. |
| `message` | `message` | `string` | ✅ |  | Göndərilmiş mesaj. |
| `status` | `status` | `int` | ✅ |  | SMS statusu. Enum üçün `smsStatus()`. |
| `date` | `date` | `string\|null` |  |  | Yalnız `detailedReportWithDates()` cavabında olur. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `smsStatus()` | `?SmsStatus` |  |
| `isDelivered()` | `bool` |  |

## BulkSubmission

Toplu göndərilmə sorğusunun cavabı.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `responseCode` | `responseCode` | `int` | ✅ |  | Sorğunun nəticə kodu. Enum üçün `code()`. |
| `taskId` | `taskId` | `int\|null` |  |  | Hesabat sorğularında istifadə olunan tapşırıq id-si. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `code()` | `?BulkCode` |  |
| `isSuccessful()` | `bool` |  |

## DeliveryReport

`deliveryReport()` (POST) cavabı.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `message` | `message` | `string\|null` |  |  | Xəta və ya uğur mesajı. |
| `deliveryStatus` | `delivery_status` | `string\|null` |  |  | SMS statusu (məs., `DELIVERED`). |

## ReportStatus

`checkStatus()` (GET) cavabı.

Bu endpoint JSON qaytarmır — body-si sadəcə bir ədəddir (məs., `101`). Ona görə
`Data`-dan törəmir: hidrasiya ediləcək massiv yoxdur, xam body parse olunur.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `errorCode` | `errorCode` | `int\|null` |  |  | Status kodu, body ədəd deyilsə `null`. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `code()` | `?SmsCode` | Status kodunun enum qarşılığı, tanınırsa. |
| `isDelivered()` | `bool` |  |

## SmsPostResult

`sendSmsPost()` cavabı.

GET variantından yeganə fərqi: LSIM burada `errorCode`-u **ad** kimi qaytarır
(məs., `INTERNAL_ERROR`), rəqəm kimi yox.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `successMessage` | `successMessage` | `string\|null` |  |  | Uğurlu sorğu mesajı. |
| `errorMessage` | `errorMessage` | `string\|null` |  |  | Xəta mesajı. |
| `obj` | `obj` | `int\|null` |  |  | Uğurlu göndərilmədə transaction id. |
| `errorCode` | `errorCode` | `string\|null` |  |  | Status **adı** (məs., `INTERNAL_ERROR`). |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `isSuccessful()` | `bool` |  |

## SmsResult

Tək SMS API-sinin ümumi cavabı (GET endpoint-ləri və balans).

`obj` field-inin mənası sorğudan asılıdır:

- `sendSms()` — uğurlu göndərilmədə **transaction id**;
- `balance()` — qalan **SMS sayı**.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `successMessage` | `successMessage` | `string\|null` |  |  | Uğurlu sorğu mesajı. |
| `errorMessage` | `errorMessage` | `string\|null` |  |  | Xəta mesajı. |
| `obj` | `obj` | `int\|null` |  |  | Sorğudan asılı dəyər (transaction id və ya balans). |
| `errorCode` | `errorCode` | `int\|null` |  |  | Status kodu. Enum üçün `code()` metoduna baxın. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `code()` | `?SmsCode` | Status kodunun enum qarşılığı, tanınırsa. |
| `isSuccessful()` | `bool` | Mənfi kod xətadır; kod yoxdursa, `errorMessage`-ə baxılır. |
