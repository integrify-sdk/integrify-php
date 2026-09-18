# CloposClient

Clopos Open API v2 — POS sistemi ilə inteqrasiya.

Bu, kitabxanadakı yeganə **ödəniş şlüzü olmayan** inteqrasiyadır: burada sifariş
qəbul edilir, menyu oxunur, çek bağlanır. Pul hərəkəti POS-un öz içindədir.

```php
$client = new CloposClient(CloposConfig::fromEnvironment());

$client->authenticate();                       // token bir saat yaşayır

foreach ($client->listProducts() as $product) {
    echo $product->name, ' — ', $product->price, PHP_EOL;
}
```

## Token

Token klientin **içində** saxlanılır və hər sorğuya `x-token` header-i kimi əlavə
olunur; Python-dakı kimi onu hər çağırışda əl ilə ötürmək lazım deyil. Bir saat
yaşadığı üçün cache-ləmək istəsəniz `token()` və `useToken()` açıqdır:

```php
$client = new CloposClient($config, token: $cache->get('clopos'));

if ($client->token() === null) {
    $cache->set('clopos', $client->authenticate()->token, ttl: 3500);
}
```

## Səhifələmə

Siyahı qaytaran metodlar `Page` qaytarır. `Page` özü siyahı kimi davranır —
`foreach` ilə birbaşa gəzilir — lakin `total` da onun üzərindədir.

## Xətalar

Clopos xətanı HTTP status kodu ilə bildirir, ona görə uğursuzluq həmişə
`RequestRejected` kimi qalxır; servisin izahı exception-un `errors` field-indədir.

## `token()`

Hazırkı token, yoxdursa `null`.

```php
token(): ?string
```

## `useToken()`

Xaricdən (məs. cache-dən) gələn token-i quraşdırır.

```php
useToken(string $token): void
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$token` | `string` | ✅ |  |

## `authenticate()`

Token alır və klientdə saxlayır.

**POST** `/auth`

Token bir saat yaşayır. Bu, `x-token` header-i **göndərmədən** atılan yeganə
sorğudur — köhnə token yeni token istəyərkən mənasızdır.

```php
authenticate(): AuthToken
```

**Atır:**

- `MissingConfiguration` — Dörd açardan biri yoxdursa.
- `RequestRejected`
- `ValidationFailed`

## `listVenues()`

Brendin filialları.

**GET** `/venues`

```php
listVenues(int|null $page, int|null $limit): Page<Venue>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi (1-dən başlayır). |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `listUsers()`

POS istifadəçiləri.

**GET** `/users`

```php
listUsers(int|null $page, int|null $limit): Page<User>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `getUser()`

Bir istifadəçi.

**GET** `/users/{id}`

```php
getUser(int $id): User
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | İstifadəçinin IDsi. |

**Atır:**

- `RequestRejected` — Belə istifadəçi yoxdursa (HTTP 404).
- `ValidationFailed`

## `listCustomers()`

Müştərilər.

**GET** `/customers`

```php
$client->listCustomers(
    with: [CustomerRelation::Group, CustomerRelation::Balance],
    filters: [new CustomerFilter(CustomerFilterField::Name, 'John Doe')],
);
```

```php
listCustomers(int|null $page, int|null $limit, list<CustomerRelation>|null $with, list<CustomerFilter>|null $filters): Page<Customer>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |
| `$with` | `list<CustomerRelation>\|null` |  | Əlavə yüklənəcək əlaqələr. |
| `$filters` | `list<CustomerFilter>\|null` |  | Axtarış filtrləri. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `getCustomer()`

Bir müştəri.

**GET** `/customers/{id}`

```php
getCustomer(int $id): Customer
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | Müştərinin IDsi. |

**Atır:**

- `RequestRejected` — Belə müştəri yoxdursa (HTTP 404).
- `ValidationFailed`

## `createCustomer()`

Yeni müştəri yaradır.

**POST** `/customers`

```php
createCustomer(string $name, string|null $email, string|null $phone, string|null $code, string|null $cid, string|null $description, int|null $groupId, Gender|null $gender, string|DateTimeInterface|null $dateOfBirth): Customer
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$name` | `string` | ✅ | Müştərinin adı — yeganə məcburi field. |
| `$email` | `string\|null` |  | Email ünvanı. |
| `$phone` | `string\|null` |  | Telefon nömrəsi. Brend daxilində unikal olmalıdır. |
| `$code` | `string\|null` |  | Müştərinin kodu. Brend daxilində unikal olmalıdır. |
| `$cid` | `string\|null` |  | POS identifikatoru. |
| `$description` | `string\|null` |  | Qeyd. |
| `$groupId` | `int\|null` |  | Müştəri qrupunun IDsi. |
| `$gender` | `Gender\|null` |  | Cinsi. |
| `$dateOfBirth` | `string\|DateTimeInterface\|null` |  | Doğum tarixi (`YYYY-MM-DD`). |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `listCustomerGroups()`

Müştəri qrupları.

**GET** `/customer-groups`

```php
listCustomerGroups(int|null $page, int|null $limit): Page<CustomerGroup>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `listCategories()`

Menyu kateqoriyaları.

**GET** `/categories`

```php
listCategories(int|null $page, int|null $limit, int|null $parentId, CategoryType|null $type, bool|null $includeChildren, bool|null $includeInactive): Page<Category>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |
| `$parentId` | `int\|null` |  | Yalnız bu kateqoriyanın altındakılar. |
| `$type` | `CategoryType\|null` |  | Kateqoriyanın növü. |
| `$includeChildren` | `bool\|null` |  | Alt kateqoriyalar da qaytarılsınmı. |
| `$includeInactive` | `bool\|null` |  | Deaktiv kateqoriyalar da qaytarılsınmı. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `getCategory()`

Bir kateqoriya.

**GET** `/categories/{id}`

```php
getCategory(int $id, bool|null $includeChildren): Category
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | Kateqoriyanın IDsi. |
| `$includeChildren` | `bool\|null` |  | Alt kateqoriyalar da qaytarılsınmı. |

**Atır:**

- `RequestRejected` — Belə kateqoriya yoxdursa (HTTP 404).
- `ValidationFailed`

## `listStations()`

Stansiyalar.

**GET** `/stations`

```php
listStations(int|null $page, int|null $limit, int|null $status, bool|null $canPrint): Page<Station>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |
| `$status` | `int\|null` |  | `1` aktiv, `0` deaktiv. |
| `$canPrint` | `bool\|null` |  | Yalnız printerə yönləndirilə bilənlər. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `getStation()`

Bir stansiya.

**GET** `/stations/{id}`

```php
getStation(int $id): Station
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | Stansiyanın IDsi. |

**Atır:**

- `RequestRejected` — Belə stansiya yoxdursa (HTTP 404).
- `ValidationFailed`

## `listProducts()`

Məhsullar.

**GET** `/products`

```php
$client->listProducts(
    selects: ['id', 'name'],
    filters: new ProductFilter(giftable: true, type: [ProductType::Dish]),
);
```

```php
listProducts(int|null $page, int|null $limit, list<string>|string|null $selects, ProductFilter|null $filters): Page<Product>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |
| `$selects` | `list<string>\|string\|null` |  | Yalnız sadalanan field-lər qaytarılır. |
| `$filters` | `ProductFilter\|null` |  | Filtrlər. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `getProduct()`

Bir məhsul.

**GET** `/products/{id}`

```php
getProduct(int $id, list<ProductRelation>|null $with): Product
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | Məhsulun IDsi. |
| `$with` | `list<ProductRelation>\|null` |  | Əlavə yüklənəcək əlaqələr. |

**Atır:**

- `RequestRejected` — Belə məhsul yoxdursa (HTTP 404).
- `ValidationFailed`

## `getStopList()`

Stop-list — qalığı azalmış məhsullar.

**GET** `/products/stop-list`

```php
$client->getStopList(new StopListFilter(StopListFilterField::Limit, from: 0, to: 10));
```

```php
getStopList(StopListFilter $...filters): Page<StopListEntry>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$...filters` | `StopListFilter` |  | Aralıq filtrləri. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `listSaleTypes()`

Satış növləri.

**GET** `/sale-types`

```php
listSaleTypes(int|null $page, int|null $limit): Page<SaleType>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `listPaymentMethods()`

Ödəniş metodları.

**GET** `/payment-methods`

```php
listPaymentMethods(int|null $page, int|null $limit): Page<PaymentMethod>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `listOrders()`

Sifarişlər.

**GET** `/orders`

```php
listOrders(int|null $page, int|null $limit, OrderStatus|null $status): Page<Order>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |
| `$status` | `OrderStatus\|null` |  | Yalnız bu vəziyyətdəkilər. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `getOrder()`

Bir sifariş.

**GET** `/orders/{id}`

```php
getOrder(int $id, OrderRelation|null $with): Order
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | Sifarişin IDsi. |
| `$with` | `OrderRelation\|null` |  | Əlavə yüklənəcək əlaqə — Clopos burada **bir** dəyər qəbul edir. |

**Atır:**

- `RequestRejected` — Belə sifariş yoxdursa (HTTP 404).
- `ValidationFailed`

## `createOrder()`

Yeni sifariş yaradır.

**POST** `/orders`

```php
createOrder(int $customerId, NewOrder $order, ?array $meta): Order
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$customerId` | `int` | ✅ | Sifarişi verən müştərinin IDsi. |
| `$order` | `NewOrder` | ✅ | Sifarişin tərkibi. |
| `$meta` | `?array` |  | Sifarişin əlavə məlumatları (endirim, şərh və s.). |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `updateOrderStatus()`

Sifarişin vəziyyətini dəyişir.

**PUT** `/orders/{id}`

`id` yalnız URL-dədir — body-də getmir.

```php
updateOrderStatus(int $id, OrderStatus $status): Order
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | Sifarişin IDsi. |
| `$status` | `OrderStatus` | ✅ | Yeni vəziyyət. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `listReceipts()`

Çeklər.

**GET** `/receipts`

```php
listReceipts(int|null $page, int|null $limit, string|null $sortBy, int|null $sortOrder, string|DateTimeInterface|null $dateFrom, string|DateTimeInterface|null $dateTo): Page<Receipt>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |
| `$sortBy` | `string\|null` |  | Sıralama field-i, məs. `created_at`. |
| `$sortOrder` | `int\|null` |  | `1` artan, `-1` azalan. |
| `$dateFrom` | `string\|DateTimeInterface\|null` |  | Aralığın başlanğıcı (ISO 8601). |
| `$dateTo` | `string\|DateTimeInterface\|null` |  | Aralığın sonu (ISO 8601). |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `getReceipt()`

Bir çek.

**GET** `/receipts/{id}`

```php
getReceipt(int $id): Receipt
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | Çekin IDsi. |

**Atır:**

- `RequestRejected` — Belə çek yoxdursa (HTTP 404).
- `ValidationFailed`

## `updateClosedReceipt()`

Bağlanmış çekin bir neçə field-ini dəyişir.

**PATCH** `/receipts/{id}`

> `id` **həm URL-də, həm də body-də** gedir. Bu, digər endpoint-lərdən fərqlidir
> (məs. `updateOrderStatus()` onu yalnız URL-də göndərir) və Python
> kitabxanasındakı davranışla eynidir.

```php
updateClosedReceipt(int $id, OrderStatus|null $orderStatus, string|null $orderNumber, string|null $fiscalId, bool|null $lock): Receipt
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | Çekin IDsi. |
| `$orderStatus` | `OrderStatus\|null` |  | Sifarişin yeni vəziyyəti. |
| `$orderNumber` | `string\|null` |  | Sifariş nömrəsi. |
| `$fiscalId` | `string\|null` |  | Fiskal identifikator. |
| `$lock` | `bool\|null` |  | Çek kilidlənsinmi. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `closeReceipt()`

Açıq çeki bağlayır.

**POST** `/receipts/{id}/close`

> `id` burada da həm URL-də, həm body-də gedir.

```php
closeReceipt(int $id, string $cid, list<ReceiptPayment> $payments, string|DateTimeInterface $closedAt): Receipt
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | Çekin IDsi. |
| `$cid` | `string` | ✅ | Çekin POS identifikatoru (UUID). |
| `$payments` | `list<ReceiptPayment>` | ✅ | Ödəniş sətirləri — cəmi çekin qalığını örtməlidir. |
| `$closedAt` | `string\|DateTimeInterface` |  | Bağlanma vaxtı (ISO 8601). Boş sətir "indi" deməkdir. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `listReceiptStockOperations()`

Çekin yaratdığı anbar hərəkətləri.

**GET** `/receipts/{id}/stock-operations`

```php
listReceiptStockOperations(int $id): Page<ReceiptStockOperation>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$id` | `int` | ✅ | Çekin IDsi. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `listPriceLists()`

Qiymət cədvəlləri.

**GET** `/price-lists`

```php
listPriceLists(int|null $page, int|null $limit): Page<PriceList>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`

## `listPriceListPrices()`

Qiymət cədvəllərindəki qiymətlər.

**GET** `/price-lists/prices`

```php
listPriceListPrices(int|null $page, int|null $limit): Page<PriceListPrice>
```

| Parametr | Tip | Məcburi | Təsvir |
| :--- | :--- | :---: | :--- |
| `$page` | `int\|null` |  | Səhifə nömrəsi. |
| `$limit` | `int\|null` |  | Səhifədəki element sayı. |

**Atır:**

- `RequestRejected`
- `ValidationFailed`
