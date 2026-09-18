# Enum-lar

## BulkOperation

Toplu SMS API-si tək endpoint-dir (`/smxml/api`); nə edildiyini payload-dakı
`operation` field-i bildirir.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `BulkOperation::Submit` | `submit` |  |
| `BulkOperation::Report` | `report` |  |
| `BulkOperation::DetailedReport` | `detailedreport` |  |
| `BulkOperation::DetailedReportWithDate` | `detailedreportwithdate` |  |
| `BulkOperation::Units` | `units` |  |

## Endpoint

Tək SMS API-sinin endpoint-ləri (`https://apps.lsim.az`).

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `Endpoint::SendSmsGet` | `/quicksms/v1/send` |  |
| `Endpoint::SendSmsPost` | `/quicksms/v1/smssender` |  |
| `Endpoint::Balance` | `/quicksms/v1/balance` |  |
| `Endpoint::ReportGet` | `/quicksms/v1/report` |  |
| `Endpoint::ReportPost` | `/quicksms/v1/smsreporter` |  |

## BulkCode

Toplu SMS tranzaksiyasının `responsecode` dəyərləri.

Cavab DTO-larında tip kimi istifadə olunmur (bax: `SmsCode`); xam `int` saxlanılır.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `BulkCode::Success` | `0` |  |
| `BulkCode::InProcessNotReady` | `1` |  |
| `BulkCode::Duplicate` | `2` |  |
| `BulkCode::BadRequest` | `100` |  |
| `BulkCode::OperationTypeEmpty` | `101` |  |
| `BulkCode::InvalidOperation` | `102` |  |
| `BulkCode::EmptyLogin` | `103` |  |
| `BulkCode::EmptyPassword` | `104` |  |
| `BulkCode::InvalidAuth` | `105` |  |
| `BulkCode::EmptyTitle` | `106` |  |
| `BulkCode::InvalidTitle` | `107` |  |
| `BulkCode::EmptyTaskId` | `108` |  |
| `BulkCode::InvalidTaskId` | `109` |  |
| `BulkCode::EmptyControlId` | `110` |  |
| `BulkCode::EmptyScheduledDate` | `111` |  |
| `BulkCode::InvalidScheduledDate` | `112` |  |
| `BulkCode::OldScheduledDate` | `113` |  |
| `BulkCode::EmptyIsBulk` | `114` |  |
| `BulkCode::InvalidIsBulk` | `115` |  |
| `BulkCode::InvalidBulkMessage` | `116` |  |
| `BulkCode::InvalidBody` | `117` |  |
| `BulkCode::InsufficientBalance` | `118` |  |
| `BulkCode::UnknownError` | `235` | LSIM dokumentasiyasında yoxdur, lakin praktikada qayıdır. |

## SmsCode

Tək SMS API-sinin `errorCode` dəyərləri.

Müsbət kodlar SMS-in vəziyyətini, mənfi kodlar sorğunun xətasını bildirir.

Cavab DTO-larında bu enum **tip kimi istifadə olunmur** — LSIM sabah yeni kod
əlavə etsə, validasiya sınmamalıdır. DTO xam `int` saxlayır, bu enum isə
`SmsResult::code()` vasitəsilə əlçatandır.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `SmsCode::InQueue` | `100` |  |
| `SmsCode::Delivered` | `101` |  |
| `SmsCode::Undelivered` | `102` |  |
| `SmsCode::Expired` | `103` |  |
| `SmsCode::Rejected` | `104` |  |
| `SmsCode::Cancelled` | `105` |  |
| `SmsCode::Error` | `106` |  |
| `SmsCode::Unknown` | `107` |  |
| `SmsCode::Sent` | `108` |  |
| `SmsCode::BlackListed` | `109` |  |
| `SmsCode::InvalidKey` | `-100` |  |
| `SmsCode::TooLongText` | `-101` |  |
| `SmsCode::WrongNumberFormat` | `-102` |  |
| `SmsCode::InvalidSenderName` | `-103` |  |
| `SmsCode::InsufficientBalance` | `-104` |  |
| `SmsCode::NumberInBlackList` | `-105` |  |
| `SmsCode::InvalidTransactionId` | `-106` |  |
| `SmsCode::IpAddressNotAllowed` | `-107` |  |
| `SmsCode::InvalidHash` | `-108` |  |
| `SmsCode::NoHost` | `-109` |  |
| `SmsCode::ReportingLimitExceeded` | `-110` |  |
| `SmsCode::InternalError` | `-500` |  |

## SmsStatus

Toplu göndərilmədə hər bir SMS-in statusu.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `SmsStatus::Expired` | `1` |  |
| `SmsStatus::Delivered` | `2` |  |
| `SmsStatus::Undelivered` | `3` |  |
| `SmsStatus::Sent` | `4` |  |
| `SmsStatus::SystemError` | `5` |  |
| `SmsStatus::BlackList` | `6` |  |
| `SmsStatus::InQueue` | `7` |  |
| `SmsStatus::Duplicate` | `8` |  |
