# Enum-lar

## Endpoint

EPoint API-sinin endpoint-ləri (`https://epoint.az`).

Yolların bəziləri adı ilə gözləniləni etmir — EPoint-in öz adlandırmasıdır və
dəyişdirilə bilməz:

- `/api/1/refund-request` **payout**-dur (saxlanılmış karta pul köçürmə),
- `/api/1/reverse` isə əsl **refund**-dur (ödənişin geri qaytarılması).

İkisini bir-biri ilə dəyişmək pulu səhv istiqamətdə hərəkət etdirir, ona görə
enum case adları niyyəti, `value` isə EPoint-in yolunu göstərir.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `Endpoint::Pay` | `/api/1/request` |  |
| `Endpoint::GetStatus` | `/api/1/get-status` |  |
| `Endpoint::SaveCard` | `/api/1/card-registration` |  |
| `Endpoint::PayWithSavedCard` | `/api/1/execute-pay` |  |
| `Endpoint::PayAndSaveCard` | `/api/1/card-registration-with-pay` |  |
| `Endpoint::Payout` | `/api/1/refund-request` |  |
| `Endpoint::Refund` | `/api/1/reverse` |  |
| `Endpoint::SplitPay` | `/api/1/split-request` |  |
| `Endpoint::SplitPayWithSavedCard` | `/api/1/split-execute-pay` |  |
| `Endpoint::SplitPayAndSaveCard` | `/api/1/split-card-registration-with-pay` |  |

## TransactionStatus

Ödəniş sorğularının `status` dəyərləri.

Cavab DTO-larında bu enum **tip kimi istifadə olunmur** — EPoint sabah yeni
status əlavə etsə, validasiya sınmamalıdır. DTO xam `string` saxlayır, bu enum
isə DTO-nun `status()` metodu vasitəsilə əlçatandır.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `TransactionStatus::Success` | `success` |  |
| `TransactionStatus::Error` | `error` |  |
| `TransactionStatus::ServerError` | `server_error` |  |
| `TransactionStatus::Failed` | `failed` |  |

## TransactionStatusExtended

`getTransactionStatus()` cavabının `status` dəyərləri.

Ödəniş sorğularından fərqli olaraq burada tranzaksiyanın **ömür dövrü** qaytarılır,
ona görə `new` və `returned` da var, `failed` isə yoxdur — bax:
[`TransactionStatus`](#transactionstatus).

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `TransactionStatusExtended::New` | `new` |  |
| `TransactionStatusExtended::Success` | `success` |  |
| `TransactionStatusExtended::Returned` | `returned` |  |
| `TransactionStatusExtended::Error` | `error` |  |
| `TransactionStatusExtended::ServerError` | `server_error` |  |
