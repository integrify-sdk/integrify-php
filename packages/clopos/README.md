# Integrify Clopos (PHP)

[Clopos](https://clopos.com) Open API v2 inteqrasiyası — menyu, müştərilər, sifarişlər
və çeklər.

**Composer**: `integrify/clopos`
**Kod**: [https://github.com/integrify-sdk/integrify-php/tree/main/packages/clopos](https://github.com/integrify-sdk/integrify-php/tree/main/packages/clopos)
**Sənədlər**: [https://integrify.mmzeynalli.dev/integrations/clopos](https://integrify.mmzeynalli.dev/integrations/clopos)
**Rəsmi sənədlər**: [https://developer.clopos.com](https://developer.clopos.com)
**Python qarşılığı**: [`integrify-clopos`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/clopos)

> [!NOTE]
> Bu, kitabxanadakı yeganə **ödəniş şlüzü olmayan** inteqrasiyadır. Burada sifariş
> qəbul edilir, menyu oxunur, çek bağlanır — pul hərəkəti POS-un öz içindədir.

## Kitabxananın yüklənməsi

```bash
composer require integrify/clopos
composer require guzzlehttp/guzzle nyholm/psr7
```

## Konfiqurasiya

| Dəyişən | Məcburi | Təsvir |
| :--- | :---: | :--- |
| `CLOPOS_CLIENT_ID` | token üçün | Clopos-un verdiyi client ID |
| `CLOPOS_CLIENT_SECRET` | token üçün | Clopos-un verdiyi client secret |
| `CLOPOS_BRAND` | token üçün | Brend adı |
| `CLOPOS_INTEGRATOR_ID` | token üçün | Clopos-un verdiyi inteqrator IDsi |
| `CLOPOS_VENUE_ID` | ❌ | Filial IDsi — hər sorğuya `x-venue` header-i kimi gedir |

```php
use Integrify\Clopos\CloposClient;
use Integrify\Clopos\CloposConfig;

$client = new CloposClient(CloposConfig::fromEnvironment());

$client->authenticate();
```

`x-brand` header-i **lazım deyil**: v2-də brend, filial və inteqrator token-in
(JWT-nin) içində kodlanıb.

## Token

Token klientin içində saxlanılır və hər sorğuya `x-token` kimi əlavə olunur. Bir saat
yaşayır, ona görə cache-ləmək məntiqlidir:

```php
$client = new CloposClient($config, token: $cache->get('clopos'));

if ($client->token() === null) {
    $cache->set('clopos', $client->authenticate()->token, ttl: 3500);
}
```

> Python kitabxanası token-i klientdə saxlamır — onu **hər çağırışda**
> `headers={'x-token': ...}` kimi ötürmək lazımdır.

## Səhifələmə

Siyahı qaytaran metodlar `Page` qaytarır. `Page` özü siyahı kimi davranır, yəni
çağıran heç bir zərf açmır:

```php
$page = $client->listProducts(page: 1, limit: 50);

foreach ($page as $product) {
    echo $product->name, ' — ', $product->price, PHP_EOL;
}

count($page);    // bu səhifədə neçə element var
$page->total;    // serverdə neçə element var
$page->sorts;    // sıralamaya icazə verilən field-lər
```

## Sorğular

| Metod | Clopos API |
| :--- | :--- |
| `authenticate()` | **POST** `/auth` |
| `listVenues()` | **GET** `/venues` |
| `listUsers()` / `getUser()` | **GET** `/users`, `/users/{id}` |
| `listCustomers()` / `getCustomer()` / `createCustomer()` | **GET/POST** `/customers` |
| `listCustomerGroups()` | **GET** `/customer-groups` |
| `listCategories()` / `getCategory()` | **GET** `/categories`, `/categories/{id}` |
| `listStations()` / `getStation()` | **GET** `/stations`, `/stations/{id}` |
| `listProducts()` / `getProduct()` | **GET** `/products`, `/products/{id}` |
| `getStopList()` | **GET** `/products/stop-list` |
| `listSaleTypes()` / `listPaymentMethods()` | **GET** `/sale-types`, `/payment-methods` |
| `listOrders()` / `getOrder()` | **GET** `/orders`, `/orders/{id}` |
| `createOrder()` | **POST** `/orders` |
| `updateOrderStatus()` | **PUT** `/orders/{id}` |
| `listReceipts()` / `getReceipt()` | **GET** `/receipts`, `/receipts/{id}` |
| `updateClosedReceipt()` | **PATCH** `/receipts/{id}` |
| `closeReceipt()` | **POST** `/receipts/{id}/close` |
| `listReceiptStockOperations()` | **GET** `/receipts/{id}/stock-operations` |
| `listPriceLists()` / `listPriceListPrices()` | **GET** `/price-lists`, `/price-lists/prices` |

> [!NOTE]
> Open API v2-də çek **yaratmaq və silmək** endpoint-i yoxdur. Mövcud çeki bağlamaq və
> bağlanmış çekin bir neçə field-ini dəyişmək olar.

## Filtrlər

Filtrlər tipli obyektlərdir; sahə adları enum-dur, yəni səhv ad yazmaq mümkün deyil.

```php
use Integrify\Clopos\Dto\Request\CustomerFilter;
use Integrify\Clopos\Dto\Request\ProductFilter;
use Integrify\Clopos\Dto\Request\StopListFilter;
use Integrify\Clopos\Enum\CustomerFilterField;
use Integrify\Clopos\Enum\ProductType;
use Integrify\Clopos\Enum\StopListFilterField;

$client->listCustomers(
    filters: [new CustomerFilter(CustomerFilterField::Name, 'John Doe')],
);

$client->listProducts(
    selects: ['id', 'name'],
    filters: new ProductFilter(type: [ProductType::Dish], giftable: true),
);

$client->getStopList(new StopListFilter(StopListFilterField::Limit, from: 0, to: 10));
```

## Sifariş yaratmaq

```php
use Integrify\Clopos\Dto\Request\NewOrder;
use Integrify\Clopos\Dto\Request\NewOrderCustomer;
use Integrify\Clopos\Dto\Request\NewOrderProduct;
use Integrify\Clopos\Dto\Request\NewOrderService;

$order = $client->createOrder(
    customerId: 1,
    order: new NewOrder(
        service: new NewOrderService(2, 'Delivery', 1, 'Main'),
        customer: new NewOrderCustomer(9, 'Rəşid Axundzadə', phone: '+994705401040'),
        products: [new NewOrderProduct(productId: 1, count: 2)],
    ),
    meta: ['comment' => '', 'orderTotal' => '16.2000'],
);

$order->status();     // OrderStatus::New
$order->receiptId;    // POS sifarişi qəbul etdikdən sonra dolur
```

## Çeki bağlamaq

```php
use Integrify\Clopos\Dto\Request\ReceiptPayment;

$receipt = $client->closeReceipt(
    id: 9,
    cid: 'b1e1-…-uuid',
    payments: [new ReceiptPayment(id: 1, name: 'Nağd', amount: '10.50')],
    closedAt: new DateTimeImmutable('now'),
);
```

Məbləğlər **sətir** verilir və sətir qaytarılır: `float` qəpik dəqiqliyini itirə bilər.

## Xətalar

| Exception | Nə vaxt |
| :--- | :--- |
| `RequestRejected` | Clopos sorğunu rədd etdi (HTTP >= 400); `errors` servisin izahını daşıyır |
| `MissingConfiguration` | `authenticate()` üçün dörd açardan biri yoxdur |
| `ValidationFailed` | Cavab DTO-ya uyğun gəlmir, və ya zərfdə `data` yoxdur |
| `InvalidRequest` | Sorğu qurula bilmədi (yad host, doldurulmamış placeholder) |

```php
use Integrify\Clopos\Exception\RequestRejected;

try {
    $client->getUser(1000);
} catch (RequestRejected $rejection) {
    $rejection->errors[0]->exception;   // 'NotFoundHttpException'
    $rejection->errors[0]->httpCode;    // 404
    $rejection->messages();             // bütün mesajlar
    $rejection->failure->response;      // xam cavab
}
```

Hamısı `Integrify\Exception\IntegrifyException`-i implement edir.

## Python kitabxanasından fərqlər

Bu paketin payload-ları `integrify-clopos`-un payload-ları ilə **28 endpoint-dən
25-ində bayt-bayt eynidir**. Qalan üçü Python tərəfdəki xətalardır və burada
təkrarlanmır:

| Sorğu | Python nə göndərir | Burada |
| :--- | :--- | :--- |
| `listProducts()` | Bütün payload `json.dumps()`-dan keçir və httpx-ə `params` kimi verilir — query string tək bir JSON blobu olur, **heç bir filtr serverə çatmır** | Real query parametrləri |
| `listCustomers()` filtri | Dəyər yarısı `filter[i][1]` yazılır — tək halda, yəni açar səhvdir | `filters[i][1]` |
| `listCustomers()` səhifələməsi | `@model_serializer` bütün dump-ı əvəz edir və `page`/`limit` **tamamilə düşür** | Verilibsə göndərilir |
| `getOrder(with:)` | `with_` — Python tərəfdəki property adı, çünki modeldə alias yoxdur | `with` |

Bir asimmetriya isə **qəsdən** təkrarlanır: `NewOrderCustomer`-in `phone` və
`customerDiscountType` field-ləri verilməsə də payload-da `null` kimi görünür, `address`
isə tamamilə düşür. Python modelində ilk ikisinin default-u `UNSET` deyil, `None`-dur;
Clopos sifariş payload-unu JSON kimi saxlayıb geri qaytardığı üçün fərq görünə bilər.

Bundan başqa PHP tərəfin öz qərarları:

- **Token klientdədir**, hər çağırışda header kimi ötürülmür.
- **Zərf açılmır**: siyahılar `Page`, tək obyektlər isə birbaşa DTO qaytarır.
- **Uğursuzluq exception-dur**: Python `APIResponse.ok = false` qaytarır və xətanı
  `body.error`-da saxlayır, yəni yoxlamağı unutmaq mümkündür.
- Sorğu default-ları göndərilmir. Python-un `page=1, limit=50` kimi default-ları yalnız
  tip stub-larındadır (`if TYPE_CHECKING`) və işləmə zamanı tətbiq olunmur — yəni orada
  da məftilə çıxmır.

## Testlər

Sorğuları görmək üçün `RecordingTransport` kifayətdir — HTTP klienti mock etmək lazım
deyil:

```php
use Integrify\Http\RecordingTransport;
use Integrify\Response;

$transport = new RecordingTransport();
$client = new CloposClient($config, $transport, token: 'testtoken');

$transport->queue(Response::json(['success' => true, 'data' => [], 'total' => 0]));

$client->listProducts(filters: new ProductFilter(giftable: true));

$transport->lastRequest()->query;   // ['filters[giftable][0]' => 'giftable', ...]
```
