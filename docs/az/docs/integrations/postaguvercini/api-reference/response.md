---
title: Posta Güvercini · Cavab DTO-ları
---

# Cavab DTO-ları

Bu obyektlər servisdən **gəlir**. Enum-a bənzəyən field-lər sətir saxlanılır və enum ayrıca metodla verilir, ona görə servis yeni dəyər əlavə etdikdə cavab validasiyadan keçməkdə davam edir.

## Balance

Hesabdakı qalan kredit.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `balance` | `Balance` | `int\|null` |  |  | Qalan kredit sayı. |

## BalanceResult

`checkBalance()` cavabı.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `statusCode` | `StatusCode` | `int\|null` |  |  | Sorğunun nəticə kodu. `200` uğur. |
| `statusDescription` | `StatusDescription` | `string\|null` |  |  | Nəticənin izahı. |
| `result` | `Result` | `Balance\|null` |  |  | Qalan kredit. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?StatusCode` | `statusCode`-un enum qarşılığı, tanınırsa. |
| `isSuccessful()` | `bool` | Yalnız `200` uğurdur. |
| `balance()` | `?int` | Qalan kredit sayı; sorğu uğursuz olubsa `null`. |

## MessageStatus

Bir mesajın çatdırılma vəziyyəti.

Diqqət: `isFinalStatus` və `smsCharge` servisdə **sətir** kimi gəlir (`"1"`,
`"true"` və s.), bool/int kimi yox — Python sxemi də onları `str` saxlayır.
`isFinal()` həmin sətri oxuyur.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `messageId` | `MessageId` | `string\|null` |  |  | Mesajın IDsi. |
| `receiver` | `Receiver` | `string\|null` |  |  | Alıcının nömrəsi. |
| `smsStatus` | `SmsStatus` | `string\|null` |  |  | Vəziyyətin kodu. |
| `smsStatusDescription` | `SmsStatusDescription` | `string\|null` |  |  | Vəziyyətin izahı. |
| `isFinalStatus` | `IsFinalStatus` | `string\|null` |  |  | Vəziyyət yekundurmu — **sətir** kimi gəlir. |
| `statusTime` | `StatusTime` | `string\|null` |  |  | Vəziyyətin qeyd olunma vaxtı. |
| `smsCharge` | `SmsCharge` | `string\|null` |  |  | Silinən kredit sayı — **sətir** kimi gəlir. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `isFinal()` | `bool` | Vəziyyət yekundurmu. |

## SendResult

`sendSms()` və `sendMessages()` cavabı.

**Servis uğursuz sorğuya da HTTP 200 qaytarır**, ona görə `RequestFailed`
atılmaması "göndərildi" demək deyil — `isSuccessful()` yoxlamaq məcburidir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `statusCode` | `StatusCode` | `int\|null` |  |  | Nəticə kodu. `200` uğur. Enum üçün `status()`. |
| `statusDescription` | `StatusDescription` | `string\|null` |  |  | Nəticənin izahı. |
| `result` | `Result` | `list<SentMessage>\|null` |  |  | Göndərilən mesajlar. Xəta halında `null`. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?StatusCode` | `statusCode`-un enum qarşılığı, tanınırsa. |
| `isSuccessful()` | `bool` | Yalnız `200` uğurdur. |
| `messages()` | `array` | Göndərilən mesajlar — xəta halında boş siyahı. |
| `messageIds()` | `array` | Bütün mesajların IDsi — status sorğusuna birbaşa ötürülə bilər. |

## SentMessage

Göndərilmiş bir SMS-in nəticəsi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `messageId` | `MessageId` | `string\|null` |  |  | Mesajın IDsi — status sorğusunda bu istifadə olunur. |
| `receiver` | `Receiver` | `string\|null` |  |  | Alıcının nömrəsi. |
| `charge` | `Charge` | `int\|null` |  |  | Bu mesaj üçün silinən kredit sayı. |

## StatusResult

`getStatus()` cavabı.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `statusCode` | `StatusCode` | `int\|null` |  |  | Sorğunun nəticə kodu. `200` uğur. |
| `statusDescription` | `StatusDescription` | `string\|null` |  |  | Nəticənin izahı. |
| `result` | `Result` | `list<MessageStatus>\|null` |  |  | Soruşulan mesajların vəziyyəti. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?StatusCode` | `statusCode`-un enum qarşılığı, tanınırsa. |
| `isSuccessful()` | `bool` | **Sorğu** uğurlu oldumu — mesajların çatdırılması ayrı sualdır. |
| `statuses()` | `array` | Mesajların vəziyyəti — xəta halında boş siyahı. |
