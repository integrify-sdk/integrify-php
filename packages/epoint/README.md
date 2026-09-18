# Integrify EPoint (PHP)

[EPoint](https://epoint.az) ödəniş şlüzünün PHP inteqrasiyası — ödəniş, kart yaddaşı,
payout, refund və bölünmüş ödənişlər.

**Composer**: `integrify/epoint`
**Kod**: [https://github.com/integrify-sdk/integrify-php/tree/main/packages/epoint](https://github.com/integrify-sdk/integrify-php/tree/main/packages/epoint)
**Sənədlər**: [https://integrify-php.mmzeynalli.dev/integrations/epoint/](https://integrify-php.mmzeynalli.dev/integrations/epoint/)
**Python qarşılığı**: [`integrify-epoint`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/epoint)

> [!IMPORTANT]
> **EPoint uğursuz ödəniş üçün də HTTP 200 qaytarır.** Yəni `RequestFailed`
> atılmaması ödənişin alındığı demək deyil — hər cavabda `isSuccessful()` var və ona
> baxmaq məcburidir. `RequestFailed` yalnız şəbəkə problemi üçün atılır.

## Kitabxananın yüklənməsi

```bash
composer require integrify/epoint
```

Paket PSR-18 klienti və PSR-17 factory-si tələb edir (`integrify/core` onları *virtual*
paket kimi istəyir), ona görə tətbiqinizdə real implementasiya olmalıdır:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
```

## Konfiqurasiya

| Dəyişən | Məcburi | Default | Təsvir |
| :--- | :---: | :--- | :--- |
| `EPOINT_PUBLIC_KEY` | ✅ | — | Hər payload-a düşən public key |
| `EPOINT_PRIVATE_KEY` | ✅ | — | İmzalama açarı. **Heç vaxt göndərilmir** |
| `EPOINT_INTERFACE_LANG` | ❌ | `az` | Ödəniş səhifəsinin dili (`az`, `en`, `ru`) |
| `EPOINT_SUCCESS_REDIRECT_URL` | ❌ | — | Uğurlu ödənişdən sonra yönləndirmə |
| `EPOINT_FAILED_REDIRECT_URL` | ❌ | — | Uğursuz ödənişdən sonra yönləndirmə |

```php
use Integrify\EPoint\EPointClient;
use Integrify\EPoint\EPointConfig;

// Environment-dən
$client = new EPointClient(EPointConfig::fromEnvironment());

// Və ya açıq şəkildə — konfiqurasiya dəyişməz value object-dir, qlobal state yoxdur
$client = new EPointClient(new EPointConfig(
    publicKey: 'i000000001',
    privateKey: 'secret',
    successRedirectUrl: 'https://shop.az/ok',
    errorRedirectUrl: 'https://shop.az/fail',
));
```

> Diqqət: environment dəyişəni `EPOINT_FAILED_REDIRECT_URL` adlanır, API-də gedən field
> isə `error_redirect_url`. Adlar Python kitabxanası ilə eyni saxlanılıb ki, iki SDK bir
> `.env` faylını paylaşa bilsin.

## Ödəniş axını

Ödəniş **iki mərhələlidir**: `pay()` yalnız onu başladır.

```
$client->pay(...)  ──▶  redirectUrl  ──▶  müştəri kartı daxil edir
                                                    │
                       sizin callback URL-iniz  ◀───┘
                                │
                     Callback::decode($body)  ──▶  CallbackData
```

```php
$result = $client->pay(amount: 100, currency: 'AZN', orderId: 'order-1');

if (!$result->isSuccessful()) {
    // $result->status, $result->message
    return;
}

// Müştərini bura yönləndirin
return redirect($result->redirectUrl);
```

Nəticə callback URL-inizə `POST` olunur:

```php
use Integrify\EPoint\Callback;
use Integrify\EPoint\Exception\SignatureMismatch;

$callback = new Callback(EPointConfig::fromEnvironment());

try {
    $data = $callback->decode($rawRequestBody);
} catch (SignatureMismatch) {
    // Bu sorğu EPoint-dən gəlmir
    return response('', 403);
}

if ($data->isSuccessful()) {
    // $data->orderId sizin göndərdiyiniz IDdir — sifarişi bununla tapın
}
```

> [!WARNING]
> Callback URL-i internetdən əlçatandır: ona istənilən kəs sorğu ata bilər. İmza
> yoxlaması "bu datanı həqiqətən EPoint göndərdi" sualının yeganə cavabıdır, ona görə
> uyğunsuzluq `null` deyil, `SignatureMismatch` ilə bildirilir — yoxlamağı unutmaq
> mümkün olmasın.

## Sorğular

| Metod | Endpoint | Cavab |
| :--- | :--- | :--- |
| `pay()` | `POST /api/1/request` | `RedirectUrl` |
| `getTransactionStatus()` | `POST /api/1/get-status` | `TransactionStatusResult` |
| `saveCard()` | `POST /api/1/card-registration` | `RedirectUrlWithCardId` |
| `payWithSavedCard()` | `POST /api/1/execute-pay` | `PaymentResult` |
| `payAndSaveCard()` | `POST /api/1/card-registration-with-pay` | `RedirectUrlWithCardId` |
| `payout()` | `POST /api/1/refund-request` | `PaymentResult` |
| `refund()` | `POST /api/1/reverse` | `MinimalResult` |
| `splitPay()` | `POST /api/1/split-request` | `RedirectUrl` |
| `splitPayWithSavedCard()` | `POST /api/1/split-execute-pay` | `SplitPaymentResult` |
| `splitPayAndSaveCard()` | `POST /api/1/split-card-registration-with-pay` | `RedirectUrlWithCardId` |

> [!CAUTION]
> EPoint iki endpoint-i **əksinə adlandırıb**: `/api/1/refund-request` payout-dur (pul
> sizdən müştəriyə), `/api/1/reverse` isə refund-dur (ödənişin geri qaytarılması).
> Metod adları niyyəti bildirir — `payout()` və `refund()`.

### Saxlanılmış kartla ödəniş

```php
// 1. Kartı qeyd edin (ödəniş olmadan)
$registration = $client->saveCard();
// müştərini $registration->redirectUrl-ə yönləndirin; təsdiq callback ilə gəlir

// 2. Callback gəldikdən sonra kartla ödəniş edin — müştəri iştirakı olmadan
$result = $client->payWithSavedCard(
    amount: 100,
    currency: 'AZN',
    orderId: 'order-2',
    cardId: $cardId,
);

$result->isSuccessful();
$result->rrn;            // yalnız uğurlu əməliyyatda
$result->cardMask;       // 123456******1234
$result->codeMessage();  // bankın kodunun izahı
```

### Bölünmüş ödəniş

```php
$result = $client->splitPay(
    amount: 100,
    currency: 'AZN',
    orderId: 'order-3',
    splitUserId: 'epoint_user_id',  // ikinci EPoint istifadəçisi
    splitAmount: 50,
);
```

### Geri qaytarma

```php
$client->refund('texxxxxx', 'AZN');       // tam
$client->refund('texxxxxx', 'AZN', 50);   // yarımçıq
```

## Status sorğusu iki ayrı sual verir

```php
$status = $client->getTransactionStatus('texxxxxx');

$status->isSuccessful();  // sorğu alındımı (yalnız `server_error` = false)
$status->isPaid();        // tranzaksiya uğurlu oldumu
$status->isReturned();    // geri qaytarılıbmı
```

Status sorğusunda `error` **normal cavabdır** — "soruşduğun tranzaksiya uğursuz olub",
yəni sorğunun özü uğurludur. İkisini bir-biri ilə dəyişmək uğursuz ödənişi uğurlu kimi
oxumaq deməkdir. Digər bütün cavablarda `isSuccessful()` sadəcə `status === 'success'`
yoxlayır.

## Məbləğlər

Metodlar `int|float|string` qəbul edir, EPoint isə məbləği JSON **sətri** kimi gözləyir
(`"100"`, `"10.50"`).

```php
$client->pay(100, ...);        // "100"
$client->pay('10.50', ...);    // "10.50"  — sonda sıfır qorunur
$client->pay(10.55, ...);      // "10.55"
```

Pul üçün `string` və ya `int` tövsiyə olunur. `float` qəbul edilir, lakin:

- `0.1 + 0.2` PHP-də `0.30000000000000004`-dür; bu paket onu `"0.3"` kimi göndərir
  (Python kitabxanası eyni girişdə tam `"0.30000000000000004"` göndərir);
- `(string) 1e20` PHP-də `"1.0E+20"` verir və EPoint eksponensial yazılışı qəbul etmir,
  ona görə çevirmə heç vaxt eksponensial nəticə vermir;
- `'1e5'` kimi sətirlər `InvalidRequest` ilə rədd olunur, halbuki `is_numeric()` onları
  qəbul edir.

## Status və kodlar

```php
$result->status;          // xam sətir — EPoint yeni dəyər əlavə etsə də sınmır
$result->status();        // TransactionStatus|null
$result->code;            // bankın xam kodu, məs. '000'
$result->codeMessage();   // 'Təsdiq edildi', tanınmayan kod üçün null
```

Enum-lar DTO property tipi kimi **istifadə olunmur**: EPoint sabah yeni status əlavə
etsə, validasiya sınmamalıdır. `BankCode::all()` 102 tanınan bank kodunu qaytarır.

## Xətalar

| Exception | Nə vaxt |
| :--- | :--- |
| `MissingConfiguration` | `EPOINT_PUBLIC_KEY`/`EPOINT_PRIVATE_KEY` yoxdur |
| `InvalidRequest` | Məbləğ ədəd deyil, sonlu deyil, və ya eksponensial yazılışdadır |
| `SignatureMismatch` | Callback-in imzası uyğun gəlmir, və ya body formatı yanlışdır |
| `ValidationFailed` | Cavab (və ya callback datası) DTO-ya uyğun gəlmir |
| `RequestFailed` | Şəbəkə xətası, və ya HTTP >= 400 |

Hamısı `Integrify\Exception\IntegrifyException`-i implement edir.

## Testlər

Testlər `RecordingTransport` inject edir — HTTP klienti heç vaxt mock olunmur:

```php
use Integrify\Http\RecordingTransport;
use Integrify\Response;

$transport = new RecordingTransport();
$client = new EPointClient($config, $transport);

$transport->queue(Response::json(['status' => 'success', 'redirect_url' => 'https://epoint.az/']));

$client->pay(100, 'AZN', 'order-1');

// Zərfi açıb payload-a baxın
$body = $transport->lastRequest()->body;
$payload = json_decode(base64_decode($body['data']), true);
```

Məftildə yalnız zərf gedir — `{data, signature}` — yəni payload base64-lənmiş JSON-dur.
Private key nə payload-da, nə header-də görünür.
