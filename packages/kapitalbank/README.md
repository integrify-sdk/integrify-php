# Integrify Kapital Bank (PHP)

[Kapital Bank](https://kapitalbank.az) e-commerce şlüzünün PHP inteqrasiyası — sifariş,
kart yaddaşı, clearing, ləğv, geri qaytarma və saxlanılmış kartla ödəniş.

**Composer**: `integrify/kapitalbank`
**Kod**: [https://github.com/integrify-sdk/integrify-php/tree/main/packages/kapitalbank](https://github.com/integrify-sdk/integrify-php/tree/main/packages/kapitalbank)
**Sənədlər**: [https://integrify-php.mmzeynalli.dev/integrations/kapitalbank/](https://integrify-php.mmzeynalli.dev/integrations/kapitalbank/)
**Python qarşılığı**: [`integrify-kapitalbank`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/kapitalbank)

> [!IMPORTANT]
> EPoint-dən fərqli olaraq **Kapital Bank uğursuz sorğuya 400-dən böyük status
> qaytarır**, ona görə burada `isSuccessful()` yoxdur: uğursuzluq `RequestRejected`
> kimi qalxır və bankın `errorCode`-unu daşıyır.

## Kitabxananın yüklənməsi

```bash
composer require integrify/kapitalbank
```

Paket PSR-18 klienti və PSR-17 factory-si tələb edir:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
```

## Konfiqurasiya

| Dəyişən | Məcburi | Default | Təsvir |
| :--- | :---: | :--- | :--- |
| `KAPITAL_USERNAME` | ✅ | — | Merchant istifadəçi adı |
| `KAPITAL_PASSWORD` | ✅ | — | Merchant parolu |
| `KAPITAL_ENV` | ❌ | `test` | `test` və ya `prod` |
| `KAPITAL_INTERFACE_LANG` | ❌ | `az` | Ödəniş səhifəsinin dili |
| `KAPITAL_REDIRECT_URL` | ❌ | — | Ödənişdən sonra qayıdılan URL |

```php
use Integrify\Environment;
use Integrify\Kapitalbank\KapitalClient;
use Integrify\Kapitalbank\KapitalConfig;

$client = new KapitalClient(KapitalConfig::fromEnvironment());

// və ya açıq şəkildə
$client = new KapitalClient(new KapitalConfig(
    username: 'merchant',
    password: 'secret',
    environment: Environment::Prod,
    redirectUrl: 'https://shop.az/done',
));
```

| Mühit | Ünvan |
| :--- | :--- |
| `test` | `https://txpgtst.kapitalbank.az` |
| `prod` | `https://e-commerce.kapitalbank.az` |

> [!WARNING]
> `KAPITAL_ENV` **strict** oxunur: `prd` kimi yazılış xətası `MissingConfiguration`
> atır. Python kitabxanası belə halda xəbərdarlıq edib test mühitinə düşür, lakin bir
> xəbərdarlıq asanlıqla gözdən qaçır və nəticəsi real ödənişlərin test şlüzünə
> getməsidir — və ya əksi.

## Adi ödəniş

```php
$order = $client->createOrder(amount: 10.5, currency: 'AZN', description: 'Sifariş');

$order->id;              // 1231
$order->password;        // sifarişin öz parolu (merchant parolu DEYİL)
$order->redirectUrl();   // müştərini bura yönləndirin
```

Bank hazır link qaytarmır — `redirectUrl()` onu `hppUrl`, `id` və `password`-dan yığır.

Sonra vəziyyəti soruşun:

```php
$info = $client->getOrderInformation($order->id);

$info->status();        // OrderStatus|null
$info->isFullyPaid();   // bool
```

Tam məlumat üçün:

```php
$detailed = $client->getDetailedOrderInformation($order->id);

$detailed->srcToken?->card?->brand;   // 'VISA'
$detailed->cardToken();               // sonrakı ödənişlər üçün token
$detailed->authorizedChargeAmount;    // bloklanmış məbləğ
$detailed->clearedChargeAmount;       // faktiki silinmiş məbləğ
```

## Sorğular

| Metod | Endpoint | Cavab |
| :--- | :--- | :--- |
| `createOrder()` | `POST /api/order` | `CreatedOrder` |
| `saveCard()` | `POST /api/order` | `CreatedOrder` |
| `payAndSaveCard()` | `POST /api/order` | `CreatedOrder` |
| `orderWithSavedCard()` | `POST /api/order` | `CreatedOrder` |
| `getOrderInformation()` | `GET /api/order/{id}` | `OrderInformation` |
| `getDetailedOrderInformation()` | `GET /api/order/{id}` | `DetailedOrderInformation` |
| `refundOrder()` | `POST /api/order/{id}/exec-tran` | `TransactionResult` |
| `fullReverseOrder()` | `POST /api/order/{id}/exec-tran` | `TransactionResult` |
| `clearingOrder()` | `POST /api/order/{id}/exec-tran` | `TransactionResult` |
| `partialReverseOrder()` | `POST /api/order/{id}/exec-tran` | `TransactionResult` |
| `linkCardToken()` | `POST /api/order/{id}/set-src-token` | `LinkedCardToken` |
| `processPaymentWithSavedCard()` | `POST /api/order/{id}/exec-tran` | `TransactionResult` |

Dörd tranzaksiya eyni yola gedir — fərq yalnız payload-dakı `phase` / `type` /
`voidKind` dəyərlərindədir:

| Metod | Payload |
| :--- | :--- |
| `refundOrder()` | `{phase: Single, type: Refund, amount}` |
| `fullReverseOrder()` | `{phase: Auth, voidKind: Full}` |
| `clearingOrder()` | `{phase: Clearing, amount}` |
| `partialReverseOrder()` | `{phase: Single, voidKind: Partial, amount}` |

## Bloklama və silmə

`saveCard()` `Order_DMS` növüdür: məbləğ kartda **bloklanır**, silinmir.

```php
$order = $client->saveCard(1, 'AZN', 'Kart qeydiyyatı');
// müştərini $order->redirectUrl()-ə yönləndirin

// təsdiqdən sonra:
$detailed = $client->getDetailedOrderInformation($order->id);
$token = $detailed->cardToken();

$client->clearingOrder($order->id, 1);       // bloklanmışı sil
// və ya
$client->fullReverseOrder($order->id);       // bloklanmışı azad et
```

## Saxlanılmış kartla ödəniş

```php
$result = $client->payWithSavedCard($token, 10.5, 'AZN', 'Təkrar ödəniş');

$result->isApproved();      // bool
$result->resultMessage();   // 'Approved'
$result->approvalCode;
```

> [!CAUTION]
> `payWithSavedCard()` **üç sorğudur** və atomik deyil: `orderWithSavedCard()` →
> `linkCardToken()` → `processPaymentWithSavedCard()`. İkinci və ya üçüncü addım
> uğursuz olarsa, yaradılmış sifariş bankda qalır — exception-ı tutub həmin sifarişi
> `fullReverseOrder()` ilə ləğv etmək sizin işinizdir. Addımlara ayrıca nəzarət
> lazımdırsa, metodları bir-bir çağırın.

## Məbləğlər

Metodlar `int|float|string` qəbul edir, bank isə məbləği JSON **sətri** kimi gözləyir.
Pul üçün `string` və ya `int` tövsiyə olunur — `float`-un dəqiqlik itkisi və
eksponensial yazılış problemi barədə `KapitalClient::amount()`-a baxın.

```php
$client->createOrder(10, 'AZN', 'D');        // "10"
$client->createOrder('10.50', 'AZN', 'D');   // "10.50"
```

Cavablarda da məbləğlər **sətir** saxlanılır: bank onları JSON ədədi kimi qaytarır,
`float`-a çevirmək isə qəpik dəqiqliyini itirə bilər.

## Xətalar

```php
use Integrify\Kapitalbank\Exception\RequestRejected;

try {
    $client->refundOrder($orderId, 10);
} catch (RequestRejected $rejection) {
    $rejection->errorCode;       // 'InvalidOrderState'
    $rejection->code();          // ErrorCode::InvalidOrderState, tanınmasa null
    $rejection->description;     // bankın izahı
    $rejection->details;         // errorDetails, varsa
    $rejection->getPrevious();   // orijinal RequestFailed — cavab burada
}
```

| Exception | Nə vaxt |
| :--- | :--- |
| `MissingConfiguration` | Məcburi dəyişən yoxdur, və ya `KAPITAL_ENV` tanınmır |
| `InvalidRequest` | Məbləğ ədəd deyil, sonlu deyil, və ya eksponensial yazılışdadır |
| `RequestRejected` | Bank sorğunu rədd etdi (400-dən böyük status) |
| `ValidationFailed` | Cavab DTO-ya uyğun gəlmir |
| `RequestFailed` | Şəbəkə xətası — bankın rəddi **deyil** |

Hamısı `Integrify\Exception\IntegrifyException`-i implement edir. Şəbəkə xətası
`RequestRejected`-ə çevrilmir: ölmüş bağlantı ilə rədd edilmiş sorğu fərqli
problemlərdir və fərqli davranış tələb edir.

## Status kodları

```php
$info->status;             // xam sətir — bank yeni dəyər əlavə etsə də sınmır
$info->status();           // OrderStatus|null
$result->pmoResultCode;    // bankın xam kodu, məs. '1'
$result->resultMessage();  // 'Approved', tanınmayan kod üçün null
```

`PmoResultCode::all()` 72 tanınan kodu qaytarır.

## Testlər

```php
use Integrify\Http\RecordingTransport;
use Integrify\Response;

$transport = new RecordingTransport();
$client = new KapitalClient($config, $transport);

$transport->queue(Response::json([
    'order' => ['id' => 1231, 'password' => 'p', 'hppUrl' => 'https://txpgtst.kapitalbank.az'],
]));

$client->createOrder(10.5, 'AZN', 'D');

$transport->lastRequest()->body['order'];   // göndərilmiş payload
```

Sorğular `{"order": {...}}` və ya `{"tran": {...}}` zərfi ilə gedir; cavab da eyni
açardan oxunur.
