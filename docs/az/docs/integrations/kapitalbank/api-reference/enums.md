---
title: Kapital Bank · Enum-lar
---

# Enum-lar

## Endpoint

Kapital Bank e-commerce API-sinin endpoint-ləri.

Dəyişən hissələr `{orderId}` şəklindədir və `Client::uri()` vasitəsilə
`rawurlencode` olunur.

Diqqət: `ExecuteTransaction` **dörd** ayrı əməliyyat üçün istifadə olunur —
refund, tam geri qaytarma, clearing və yarımçıq geri qaytarma. Fərq yalnız
payload-dakı `phase`/`type`/`voidKind` dəyərlərindədir, yolda deyil.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `Endpoint::Order` | `/api/order` |  |
| `Endpoint::GetOrder` | `/api/order/{orderId}` |  |
| `Endpoint::ExecuteTransaction` | `/api/order/{orderId}/exec-tran` |  |
| `Endpoint::LinkCardToken` | `/api/order/{orderId}/set-src-token` |  |

## ErrorCode

Bankın 400-dən böyük cavablarında qaytardığı `errorCode` dəyərləri.

`RequestRejected::code()` bunu qaytarır; tanınmayan kod üçün `null`, xam dəyər isə
`RequestRejected::$errorCode`-da qalır.

İzahlar bankın sənədlərindən olduğu kimi (ingiliscə) saxlanılıb.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `ErrorCode::InvalidAmount` | `InvalidAmt` | Invalid amount |
| `ErrorCode::InvalidRequest` | `InvalidRequest` | Invalid request |
| `ErrorCode::InvalidTransaction` | `InvalidTran` | Invalid transaction |
| `ErrorCode::InvalidTransactionLinkage` | `InvalidTranLink` | Invalid transaction linkage |
| `ErrorCode::TransactionProhibited` | `TranProhibited` | Transaction prohibited |
| `ErrorCode::ActionAppException` | `ActionAppException` |  |
| `ErrorCode::CantChooseSettlementAccount` | `CantChooseSettleAcct` | Can't choose settlement account |
| `ErrorCode::PmoInterfaceNotFound` | `PmoIfaceNotFound` | Pmo interface not found |
| `ErrorCode::PmoDecline` | `PmoDecline` | Transaction declined by PMO: {reason} |
| `ErrorCode::PmoUnreachable` | `PmoUnreachable` | Can't reach PMO |
| `ErrorCode::InvalidCertificate` | `InvalidCert` | Invalid certificate |
| `ErrorCode::InvalidLogin` | `InvalidLogin` | Invalid login or password |
| `ErrorCode::InvalidOrderState` | `InvalidOrderState` | Invalid order state |
| `ErrorCode::InvalidUserSession` | `InvalidUserSession` | Invalid user session |
| `ErrorCode::NeedChangePassword` | `NeedChangePwd` | Need change password |
| `ErrorCode::OperationProhibited` | `OperationProhibited` | Operation prohibited |
| `ErrorCode::PasswordTryLimitExceeded` | `PwdTryLimitExceeded` | Password try limit exceeded |
| `ErrorCode::UserSessionExpired` | `UserSessionExpired` | User session expired |
| `ErrorCode::CofProviderDecline` | `CofpDecline` | Declined by CoF Provider: {Reason} |
| `ErrorCode::CofProviderUnreachable` | `CofpUnreachable` | Can't reach CoF Provider |
| `ErrorCode::InvalidToken` | `InvalidToken` | Invalid token |
| `ErrorCode::InvalidAuthStatus` | `InvalidAutStatus` | Invalid authentication status |
| `ErrorCode::ConsumerNotFound` | `ConsumerNotFound` | Consumer not found |
| `ErrorCode::InvalidConsumer` | `InvalidConsumer` | Invalid consumer |
| `ErrorCode::InvalidSecret` | `InvalidSecret` | Invalid secret code |
| `ErrorCode::SecretTryLimitExceeded` | `SecretTryLimit` | Secret try limit has been exceeded |
| `ErrorCode::ServiceError` | `ServiceError` | Service error |

## OrderStatus

Sifarişin vəziyyəti.

Cavab DTO-larında bu enum **tip kimi istifadə olunmur** — Kapital Bank sabah yeni
status əlavə etsə, validasiya sınmamalıdır. DTO xam `string` saxlayır, bu enum isə
DTO-nun `status()` metodu vasitəsilə əlçatandır.

İzahlar bankın sənədlərindən olduğu kimi (ingiliscə) saxlanılıb.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `OrderStatus::BeingPrepared` | `Preparing` | Order is being prepared, no transactions have been executed on it yet. |
| `OrderStatus::Cancelled` | `Cancelled` | Order has been cancelled by the consumer (before payment). (Order is cancelled by the merchant.) |
| `OrderStatus::Rejected` | `Rejected` | Order has been rejected by the PSP (before payment). (Order is rejected by the PSP.) |
| `OrderStatus::Refused` | `Refused` | Consumer has refused to pay for the order (before payment or after unsuccessful payment attempt). (Order is refused by the consumer.) |
| `OrderStatus::Expired` | `Expired` | Order has expired (before payment). (Timeout occurs when executing the order scenario.) |
| `OrderStatus::Authorized` | `Authorized` | Order has been authorized.(Authorization transaction is executed.) |
| `OrderStatus::PartiallyPaid` | `PartiallyPaid` | Order has been partially paid. (Clearing transaction is executed for the part of the order amount.) |
| `OrderStatus::FullyPaid` | `FullyPaid` | Order has been fully paid. (Clearing transaction is executed for the full order amount (or several clearing transactions).) |
| `OrderStatus::Funded` | `Funded` | Order has been funded (debit transaction has been executed). The status can be assigned only to the order of the DualStep Transfer Order class. |
| `OrderStatus::Declined` | `Declined` | * AReq and RReq (3DS 2) could not be executed due to rejection by the issuer / error during authentication. * Operation was declined by PMO |
| `OrderStatus::Voided` | `Voided` | Authorized payment amount under the order is zero. |
| `OrderStatus::Refunded` | `Refunded` | Accounted payment amount and the accounted refund amount under the order are equal. |
| `OrderStatus::Closed` | `Closed` | Order has been closed (after payment) |
