# Cavab DTO-ları

Bu obyektlər servisdən **gəlir**. Enum-a bənzəyən field-lər sətir saxlanılır və enum ayrıca metodla verilir, ona görə servis yeni dəyər əlavə etdikdə cavab validasiyadan keçməkdə davam edir.

## CallbackData

Callback-də gələn, decode olunmuş data.

EPoint ödənişin nəticəsini sizin dashboard-da qeyd etdiyiniz URL-ə `POST` edir.
Xam body-ni `Callback::decode()`-a verin — imzanı yoxlayıb bu DTO-nu qaytarır.

`order_id` sizin sorğuda göndərdiyiniz IDdir, yəni ödənişi öz bazanızdakı sifarişlə
bu field vasitəsilə uzlaşdırırsınız.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `status` | `status` | `string\|null` |  |  | Əməliyyatın nəticəsi. |
| `message` | `message` | `string\|null` |  |  | Əməliyyatın icra statusu haqqında mesaj. |
| `transaction` | `transaction` | `string\|null` |  |  | EPoint xidmətinin əməliyyat IDsi. |
| `bankTransaction` | `bank_transaction` | `string\|null` |  |  | Bank ödəniş əməliyyatı IDsi. |
| `bankResponse` | `bank_response` | `string\|null` |  |  | Bankın cavabı. |
| `operationCode` | `operation_code` | `string\|null` |  |  | `001` — kart qeydiyyatı, `100` — istifadəçi ödənişi. |
| `rrn` | `rrn` | `string\|null` |  |  | Retrieval Reference Number. |
| `cardMask` | `card_mask` | `string\|null` |  |  | Kart maskası. |
| `cardName` | `card_name` | `string\|null` |  |  | Kart sahibinin adı. |
| `amount` | `amount` | `string\|null` |  |  | Ödəniş məbləği. |
| `code` | `code` | `string\|null` |  |  | Bankın cavab kodu. |
| `orderId` | `order_id` | `string\|null` |  | uzunluq 0–255 | Sizin sorğuda göndərdiyiniz unikal sifariş IDsi. |
| `cardId` | `card_id` | `string\|null` |  |  | Qeyd olunmuş kartın identifikatoru. |
| `splitAmount` | `split_amount` | `string\|null` |  |  | İkinci istifadəçiyə gedən məbləğ. |
| `otherAttr` | `other_attr` | `string\|null` |  |  | Sorğuda göndərdiyiniz əlavə dəyərlər. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?TransactionStatus` |  |
| `isSuccessful()` | `bool` | Ödəniş uğurlu oldumu. |
| `codeMessage()` | `?string` |  |

## MinimalResult

EPoint cavablarının ən kiçik forması — `refund()` bunu qaytarır.

**EPoint həmişə HTTP 200 qaytarır**, xəta halında da. Yəni `RequestFailed`
atılmaması ödənişin alındığı demək deyil: nəticə `status` field-indədir və
`isSuccessful()` ilə oxunur.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `status` | `status` | `string\|null` |  |  | `success`, `error`, `server_error` və ya `failed`. Enum üçün `status()` metoduna baxın. |
| `message` | `message` | `string\|null` |  |  | Əməliyyatın icra statusu haqqında mesaj. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?TransactionStatus` | `status`-un enum qarşılığı, tanınırsa. |
| `isSuccessful()` | `bool` | Yalnız `success` uğurdur. |

## PaymentResult

Server tərəfdə icra olunan ödənişlərin cavabı — `payWithSavedCard()` və
`payout()` bunu qaytarır.

`rrn`, `cardMask` və `cardName` yalnız uğurlu əməliyyatda gəlir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `status` | `status` | `string\|null` |  |  | Əməliyyatın nəticəsi. |
| `message` | `message` | `string\|null` |  |  | Əməliyyatın icra statusu haqqında mesaj. |
| `transaction` | `transaction` | `string\|null` |  |  | EPoint xidmətinin əməliyyat IDsi. |
| `bankTransaction` | `bank_transaction` | `string\|null` |  |  | Bank ödəniş əməliyyatı IDsi. |
| `bankResponse` | `bank_response` | `string\|null` |  |  | Ödənişin nəticəsi ilə bankın cavabı. |
| `operationCode` | `operation_code` | `string\|null` |  |  | `001` — kart qeydiyyatı, `100` — istifadəçi ödənişi. |
| `rrn` | `rrn` | `string\|null` |  |  | Retrieval Reference Number — unikal əməliyyat identifikatoru. Yalnız uğurlu əməliyyat üçün mövcuddur. |
| `cardMask` | `card_mask` | `string\|null` |  |  | `123456******1234` formatında kart maskası. |
| `cardName` | `card_name` | `string\|null` |  |  | Ödəniş səhifəsində göstərilən kart sahibinin adı. |
| `amount` | `amount` | `string\|null` |  |  | Ödəniş məbləği. **Sətir** saxlanılır: `float`-a çevirmək qəpik dəqiqliyini itirə bilər (`10.55` → `10.550000000000001`). |
| `code` | `code` | `string\|null` |  |  | Bankın 3 simvollu cavab kodu. İzahı üçün `codeMessage()`. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?TransactionStatus` |  |
| `isSuccessful()` | `bool` |  |
| `codeMessage()` | `?string` | Bank kodunun izahı, tanınırsa. |

## RedirectUrl

Müştərinin yönləndirilməsi lazım gələn cavab — `pay()` və `splitPay()` bunu qaytarır.

`redirectUrl` ödənişin **başlanğıcıdır**, nəticəsi deyil. Müştəri həmin URL-ə
daxil olub kart məlumatlarını yazır; nəticə isə EPoint dashboard-ında qeyd
etdiyiniz callback URL-inə gəlir və `Callback::decode()` ilə açılır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `status` | `status` | `string\|null` |  |  | Əməliyyatın nəticəsi. |
| `message` | `message` | `string\|null` |  |  | Əməliyyatın icra statusu haqqında mesaj. |
| `transaction` | `transaction` | `string\|null` |  |  | EPoint xidmətinin əməliyyat IDsi. |
| `redirectUrl` | `redirect_url` | `string\|null` |  |  | Müştərinin yönləndirilməsi lazım olan URL. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?TransactionStatus` |  |
| `isSuccessful()` | `bool` |  |

## RedirectUrlWithCardId

Kart qeydiyyatı ilə müşayiət olunan cavab — `saveCard()`, `payAndSaveCard()` və
`splitPayAndSaveCard()` bunu qaytarır.

`cardId` **hələ istifadəyə hazır deyil**: müştəri `redirectUrl`-də kartı uğurla
qeyd etməyincə onunla ödəniş etmək mümkün deyil. Təsdiq callback ilə gəlir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `status` | `status` | `string\|null` |  |  | Əməliyyatın nəticəsi. |
| `message` | `message` | `string\|null` |  |  | Əməliyyatın icra statusu haqqında mesaj. |
| `transaction` | `transaction` | `string\|null` |  |  | EPoint xidmətinin əməliyyat IDsi. |
| `redirectUrl` | `redirect_url` | `string\|null` |  |  | Müştərinin yönləndirilməsi lazım olan URL. |
| `cardId` | `card_id` | `string\|null` |  |  | Sonrakı ödənişlərdə istifadə olunacaq kart identifikatoru. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?TransactionStatus` |  |
| `isSuccessful()` | `bool` |  |

## SplitPaymentResult

`splitPayWithSavedCard()` cavabı — `PaymentResult` üstəgəl bölünən məbləğ.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `status` | `status` | `string\|null` |  |  | Əməliyyatın nəticəsi. |
| `message` | `message` | `string\|null` |  |  | Əməliyyatın icra statusu haqqında mesaj. |
| `transaction` | `transaction` | `string\|null` |  |  | EPoint xidmətinin əməliyyat IDsi. |
| `bankTransaction` | `bank_transaction` | `string\|null` |  |  | Bank ödəniş əməliyyatı IDsi. |
| `bankResponse` | `bank_response` | `string\|null` |  |  | Bankın cavabı. |
| `operationCode` | `operation_code` | `string\|null` |  |  | Əməliyyat kodu. |
| `rrn` | `rrn` | `string\|null` |  |  | Retrieval Reference Number. |
| `cardMask` | `card_mask` | `string\|null` |  |  | Kart maskası. |
| `cardName` | `card_name` | `string\|null` |  |  | Kart sahibinin adı. |
| `amount` | `amount` | `string\|null` |  |  | Ümumi ödəniş məbləği. |
| `code` | `code` | `string\|null` |  |  | Bankın cavab kodu. |
| `splitAmount` | `split_amount` | `string\|null` |  |  | İkinci istifadəçiyə gedən məbləğ. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?TransactionStatus` |  |
| `isSuccessful()` | `bool` |  |
| `codeMessage()` | `?string` |  |

## TransactionStatusResult

`getTransactionStatus()` cavabı.

Burada `isSuccessful()` digər cavablardan **fərqli** işləyir. Status sorğusunda
`error` normal cavabdır — "soruşduğun tranzaksiya uğursuz olub" — yəni sorğunun
özü uğurludur. Ona görə:

- `isSuccessful()` — **sorğu** alındımı (yalnız `server_error` halında `false`);
- `isPaid()` — **tranzaksiya** uğurlu oldumu (`status === 'success'`).

İkisini bir-biri ilə dəyişmək uğursuz ödənişi uğurlu kimi oxumaq deməkdir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `status` | `status` | `string\|null` |  |  | `new`, `success`, `returned`, `error` və ya `server_error`. |
| `message` | `message` | `string\|null` |  |  | Status haqqında mesaj. |
| `transaction` | `transaction` | `string\|null` |  |  | EPoint xidmətinin əməliyyat IDsi. |
| `bankTransaction` | `bank_transaction` | `string\|null` |  |  | Bank ödəniş əməliyyatı IDsi. |
| `bankResponse` | `bank_response` | `string\|null` |  |  | Bankın cavabı. |
| `operationCode` | `operation_code` | `string\|null` |  |  | Əməliyyat kodu. |
| `rrn` | `rrn` | `string\|null` |  |  | Retrieval Reference Number. |
| `cardMask` | `card_mask` | `string\|null` |  |  | Kart maskası. |
| `cardName` | `card_name` | `string\|null` |  |  | Kart sahibinin adı. |
| `amount` | `amount` | `string\|null` |  |  | Ödəniş məbləği. |
| `code` | `code` | `string\|null` |  |  | Bankın cavab kodu. |
| `orderId` | `order_id` | `string\|null` |  | uzunluq 0–255 | Tətbiqinizdə unikal əməliyyat IDsi. |
| `otherAttr` | `other_attr` | `string\|null` |  |  | Sorğuda göndərdiyiniz əlavə dəyərlər. EPoint bunu **sətir** kimi qaytarır, göndərdiyiniz obyekt kimi deyil. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?TransactionStatusExtended` | `status`-un enum qarşılığı, tanınırsa. |
| `isSuccessful()` | `bool` | **Sorğu** uğurlu alındımı. Tranzaksiyanın nəticəsi üçün `isPaid()`. |
| `isPaid()` | `bool` | Tranzaksiya uğurla ödənildimi. |
| `isReturned()` | `bool` | Ödəniş geri qaytarılıbmı. |
| `codeMessage()` | `?string` |  |
