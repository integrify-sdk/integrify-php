---
title: Azericard · Cavab DTO-ları
---

# Cavab DTO-ları

Bu obyektlər servisdən **gəlir**. Enum-a bənzəyən field-lər sətir saxlanılır və enum ayrıca metodla verilir, ona görə servis yeni dəyər əlavə etdikdə cavab validasiyadan keçməkdə davam edir.

## AuthCallback

Kart əməliyyatının nəticəsi — Azericard-ın callback URL-inizə post etdiyi data.

`token` və `card` yalnız `authorizeAndSaveCard()` axınında gəlir.

> [!WARNING]
> Bu callback-in `P_SIGN` dəyəri **yoxlanıla bilmir**: o, Azericard-ın öz açarı ilə
> imzalanıb və yoxlamaq üçün onların **public** açarı lazımdır, sizin private açarınız
> deyil. Python kitabxanası da yoxlamır. Ona görə callback-i tək başına "ödəniş
> oldu" sübutu saymayın — `getTransactionStatus()` ilə təsdiqləyin.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `order` | `ORDER` | `string\|null` |  |  | Sifariş IDsi. |
| `amount` | `AMOUNT` | `string\|null` |  |  | Məbləğ. **Sətir** saxlanılır. |
| `currency` | `CURRENCY` | `string\|null` |  |  | Valyuta. |
| `terminal` | `TERMINAL` | `string\|null` |  |  | Terminal IDsi. |
| `type` | `TRTYPE` | `string\|null` |  |  | Əməliyyatın `TRTYPE` dəyəri. |
| `action` | `ACTION` | `int\|null` |  |  | E-Gateway fəaliyyət kodu. Enum üçün `action()`. |
| `responseCode` | `RC` | `string\|null` |  |  | ISO-8583 Sahə 39 cavab kodu. |
| `approval` | `APPROVAL` | `string\|null` |  |  | Bankın təsdiq kodu; bank verməyibsə boş ola bilər. |
| `rrn` | `RRN` | `string\|null` |  |  | Retrieval Reference Number — `finalize()` üçün lazımdır. |
| `internalReference` | `INT_REF` | `string\|null` |  |  | Daxili istinad — `finalize()` üçün lazımdır. |
| `timestamp` | `TIMESTAMP` | `string\|null` |  |  | Əməliyyatın vaxtı. |
| `nonce` | `NONCE` | `string\|null` |  |  | Əməliyyatın nonce dəyəri. |
| `signature` | `P_SIGN` | `string\|null` |  |  | Azericard-ın MAC dəyəri. |
| `card` | `CARD` | `string\|null` |  |  | Maskalanmış kart nömrəsi — yalnız kart yaddaşı axınında. |
| `token` | `TOKEN` | `string\|null` |  |  | Saxlanılan kartın tokeni — yalnız kart yaddaşı axınında. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `action()` | `?Action` | `action`-un enum qarşılığı, tanınırsa. |
| `isSuccessful()` | `bool` | Əməliyyat uğurlu oldumu. |

## TransactionStatus

`getTransactionStatus()` cavabı.

Field adlarına diqqət: Azericard bu cavabda **boşluqlu, insan üçün yazılmış**
açarlar qaytarır (`"Transaction Status message"`, `"Banks approval code"`),
digər endpoint-lərdəki `UPPER_SNAKE` deyil. Adlar olduğu kimi saxlanılıb.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `action` | `ACTION` | `int\|null` |  |  | E-Gateway fəaliyyət kodu. Enum üçün `action()`. |
| `responseCode` | `Response code` | `string\|null` |  |  | ISO-8583 Sahə 39 cavab kodu. |
| `statusMessage` | `Transaction Status message` | `string\|null` |  |  | Vəziyyət mesajı. |
| `terminal` | `TERMINAL` | `string\|null` |  |  | Terminal IDsi. |
| `cardNumber` | `Card number` | `string\|null` |  |  | Maskalanmış kart nömrəsi. |
| `amount` | `Transaction amount` | `string\|null` |  |  | Əməliyyatın məbləği. **Sətir** saxlanılır. |
| `currency` | `Transaction currency` | `string\|null` |  |  | Əməliyyatın valyutası. |
| `date` | `Transaction date` | `string\|null` |  |  | Əməliyyatın tarixi, `YYYYMMDDHHMMSS`. |
| `state` | `Transaction state` | `string\|null` |  |  | Əməliyyatın vəziyyəti. |
| `order` | `Merchant order id` | `string\|null` |  |  | Sifariş IDsi. |
| `approval` | `Banks approval code` | `string\|null` |  |  | Bankın təsdiq kodu. |
| `rrn` | `Transaction RRN` | `string\|null` |  |  | Retrieval Reference Number. |
| `internalReference` | `INT_REF` | `string\|null` |  |  | E-ticarət şlüzünün daxili istinadı. |
| `originalType` | `Original transaction TRTYPE` | `string\|null` |  |  | Orijinal əməliyyatın `TRTYPE` dəyəri. |
| `timestamp` | `Timestamp` | `string\|null` |  |  | Sorğunun vaxtı. |
| `nonce` | `Nonce` | `string\|null` |  |  | Orijinal əməliyyatın nonce dəyəri. |
| `signature` | `P_SIGN` | `string\|null` |  |  | Azericard-ın MAC dəyəri (`P_SIGN`). |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `action()` | `?Action` | `action`-un enum qarşılığı, tanınırsa. |
| `isSuccessful()` | `bool` | Əməliyyat uğurlu oldumu. |

## TransferCallback

Pul köçürməsinin nəticəsi — Azericard-ın callback URL-inizə post etdiyi data.

Kart callback-indən fərqli olaraq bunun imzası **yoxlanıla bilir**, çünki MD5
paylaşılan açarla hesablanır. `Callback::decodeTransfer()` yoxlamanı özü aparır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `operationId` | `OperationID` | `string\|null` |  |  | Azericard-ın əməliyyat IDsi. |
| `srn` | `SRN` | `string\|null` |  |  | Sizin unikal əməliyyat nömrəniz. |
| `amount` | `Amount` | `string\|null` |  |  | Məbləğ. **Sətir** saxlanılır. |
| `currency` | `Cur` | `string\|null` |  |  | Valyuta — yalnız AZN. |
| `cardStatus` | `CardStatus` | `string\|null` |  |  | Kartın vəziyyəti. Enum üçün `cardStatus()`. |
| `receiverPan` | `ReceiverPAN` | `string\|null` |  |  | Maskalanmış kart nömrəsi. |
| `status` | `Status` | `string\|null` |  |  | Cari tranzaksiya statusu (məs. `pending`). |
| `timestamp` | `Timestamp` | `string\|null` |  |  | Cavabın vaxtı. |
| `responseCode` | `Response Code` | `string\|null` |  |  | Cavab kodu. |
| `message` | `Message` | `string\|null` |  |  | Cavab mesajı. |
| `signature` | `Signature` | `string\|null` |  |  | Azericard-ın MD5 imzası. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `cardStatus()` | `?CardStatus` | `cardStatus`-un enum qarşılığı, tanınırsa. |

## TransferResult

`confirmTransfer()` və `declineTransfer()` cavabı.

`rrn` və `receiverPan` yalnız təsdiq cavabında gəlir; rədd cavabında `null` qalır.

**İmza burada avtomatik yoxlanılmır.** Yoxlama `Callback::verifyTransferResult()`
ilə açıq şəkildə aparılır, çünki imza mənbəyi (MT açarı) DTO-ya məlum deyil — və
imzanın DTO qurulanda "gizlicə" yoxlanması, uğursuzluqda anlaşılmaz bir validasiya
xətası verərdi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `operationId` | `OperationId` | `string\|null` |  |  | Azericard-ın əməliyyat IDsi. |
| `srn` | `SRN` | `string\|null` |  |  | Sizin unikal əməliyyat nömrəniz. |
| `rrn` | `RRN` | `string\|null` |  |  | Retrieval Reference Number — yalnız təsdiqdə. |
| `amount` | `Amount` | `string\|null` |  |  | Məbləğ. **Sətir** saxlanılır. |
| `currency` | `Cur` | `string\|null` |  |  | Valyuta. |
| `receiverPan` | `ReceiverPan` | `string\|null` |  |  | Maskalanmış kart nömrəsi — yalnız təsdiqdə. |
| `status` | `Status` | `string\|null` |  |  | Vəziyyət mesajı. |
| `timestamp` | `Timestamp` | `string\|null` |  |  | Cavabın vaxtı. |
| `responseCode` | `ResponseCode` | `string\|null` |  |  | Uğur(suz)luq kodu. Enum üçün `code()`. |
| `message` | `Message` | `string\|null` |  |  | Mesaj. |
| `signature` | `Signature` | `string\|null` |  |  | Azericard-ın MD5 imzası. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `code()` | `?TransferStatusCode` | `responseCode`-un enum qarşılığı, tanınırsa. |
| `isSuccessful()` | `bool` | Yalnız `0` uğurdur. |
