# Enum-lar

## Endpoint

Azericard-ın endpoint-ləri.

**İki ayrı sistem, iki ayrı host.** MPI (3-D Secure şlüzü) kart ödənişlərini,
MT isə pul köçürmələrini idarə edir; ünvanları da, imza alqoritmləri də fərqlidir:

| Sistem | Host (test / prod) | İmza |
| :--- | :--- | :--- |
| MPI | `testmpi.3dsecure.az` / `mpi.3dsecure.az` | RSA SHA-256 (`P_SIGN`) |
| MT | `testmt.azericard.com` / `mt.azericard.com` | MD5 + paylaşılan açar |

`System` enum-u hər endpoint-in hansı hosta getdiyini bildirir.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `Endpoint::Authorization` | `/cgi-bin/cgi_link` |  |
| `Endpoint::SaveCard` | `/token/cgi_link` |  |
| `Endpoint::Transfer` | `/payment/view` |  |
| `Endpoint::TransferConfirm` | `/api/confirm` |  |
| `Endpoint::TransferDecline` | `/api/decline` |  |

## Action

E-Gateway-in fəaliyyət kodu — callback-də və status cavabında gəlir.

Cavab DTO-larında tip kimi istifadə olunmur: Azericard sabah yeni kod əlavə etsə,
validasiya sınmamalıdır.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `Action::Success` | `0` | Tranzaksiya uğurla tamamlandı. |
| `Action::Duplicate` | `1` | Duplikat əməliyyat aşkar edildi. |
| `Action::Cancelled` | `2` | Tranzaksiya rədd edildi. |
| `Action::ProcessingError` | `3` | Tranzaksiya emal xətası. |
| `Action::RepeatOfCancelled` | `6` | İmtina edilmiş əməliyyatın təkrarlanması. |
| `Action::RepeatOfUnapproved` | `7` | Doğrulama xətası ilə əməliyyatın təkrarlanması. |
| `Action::RepeatOfUnresponded` | `8` | Cavab verilmədən dayandırılmış əməliyyatın təkrarlanması. |

## AuthorizationType

Kart əməliyyatının növü (`TRTYPE`).

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `AuthorizationType::Freeze` | `0` | Məbləği kartda bloklayır, silmir. Sonra `finalize()` ilə tamamlanır. |
| `AuthorizationType::Direct` | `1` | Məbləği birbaşa silir. |

## CardStatus

Köçürmə callback-ində gələn kart vəziyyəti.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `CardStatus::Active` | `our_active` | Kart Azericard bazasındadır və aktivdir. |
| `CardStatus::Inactive` | `our_inactive` | Kart Azericard bazasındadır, lakin aktiv deyil (bloklanmış, müddəti bitmiş). |
| `CardStatus::Missing` | `foreign` | Kart Azericard bazasında yoxdur. |

## ResponseType

`finalize()` əməliyyatının növü — bloklanmış məbləğlə nə ediləcəyi.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `ResponseType::AcceptPayment` | `21` | Bloklanmış məbləği silir. |
| `ResponseType::ReturnPayment` | `22` | Ödənişi geri qaytarır. |
| `ResponseType::CancelPayment` | `24` | Bloklanmış məbləği azad edir. |

## TransferStatusCode

Pul köçürməsi cavabının `ResponseCode` dəyəri.

**Sətir-backed-dir, `int` deyil** — və bu, diqqətlə yoxlanılıb. Python sxemində
`class TransferStatusCode(str, Enum)` yazılıb, lakin dəyərlər `0`, `105` kimi ədəd
literallarıdır; `str` mixin-i onları `'0'`, `'105'` sətirlərinə çevirir. Yəni məftildə
gedən dəyər sətirdir, ədəd deyil.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `TransferStatusCode::Success` | `0` | Uğurla tamamlandı. |
| `TransferStatusCode::DuplicateTransaction` | `105` | Tranzaksiya "Gözləyən" statusunda deyil — onu rədd etmək və ya təsdiqləmək mümkün deyil. |
| `TransferStatusCode::SignatureError` | `106` | Giriş məlumatları imzaya uyğun gəlmir. |
| `TransferStatusCode::TransactionNotFound` | `112` | Ödəniş arxa tərəfdə tapılmadı. |
| `TransferStatusCode::TransactionActive` | `116` | Tranzaksiya artıq təsdiqlənib və tamamlanmasını gözləyir. |

## System

Azericard-ın iki alt sistemi.

Ayrı host, ayrı açar, ayrı imza alqoritmi — ona görə ayrı enum.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `System::Mpi` | `mpi` | 3-D Secure şlüzü: kart ödənişləri. RSA SHA-256 ilə imzalanır. |
| `System::Mt` | `mt` | Money Transfer: pul köçürmələri. MD5 + paylaşılan açar ilə imzalanır. |
