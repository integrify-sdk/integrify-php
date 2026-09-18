# Sorğu DTO-ları

Bu obyektlər servisə **göndərilir**. `Məftildəki ad` sütunu payload-da hansı açarın görünəcəyini göstərir — PHP tərəfdəki ad heç vaxt məftilə çıxmır.

## CustomerFilter

`listCustomers()` üçün bir filtr.

Bu, `Data` alt class-ı **deyil**: filtr JSON body-yə yox, query açarlarına çevrilir
(`filters[0][0]=name&filters[0][1]=John`), ona görə DTO mapper-i burada işləmir.

```php
$client->listCustomers(filters: [
    new CustomerFilter(CustomerFilterField::Name, 'John Doe'),
]);
```

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `by` | `by` | `CustomerFilterField` | ✅ |  | Filtrlənən sahə. |
| `value` | `value` | `string` | ✅ |  | Axtarılan dəyər. |

## NewOrder

`createOrder()`-ə verilən sifariş tərkibi.

```php
$order = new NewOrder(
    service: new NewOrderService(2, 'Delivery', 1, 'Main'),
    customer: new NewOrderCustomer(9, 'Rəşid Axundzadə', phone: '+994705401040'),
    products: [new NewOrderProduct(productId: 1, count: 1)],
);

$client->createOrder(customerId: 1, order: $order);
```

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `service` | `service` | `NewOrderService` | ✅ |  | Satış kanalı. |
| `customer` | `customer` | `NewOrderCustomer` | ✅ |  | Müştəri. |
| `products` | `products` | `list<NewOrderProduct>` | ✅ |  | Sifarişdəki məhsullar. |
| `meta` | `meta` | `?array` |  |  | Sifarişin əlavə məlumatları. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `toPayload()` | `array` | Payload forması. |

## NewOrderCustomer

Yeni sifarişin müştərisi.

> **`phone` və `customerDiscountType` verilməsə də məftildə görünür — `null` olaraq.**
> Python modelində bu iki field-in default dəyəri `UNSET` deyil, `None`-dur, ona görə
> pydantic onları payload-dan çıxarmır. `address` isə `UNSET` default-lu olduğu üçün
> tamamilə düşür. Clopos sifariş payload-unu JSON kimi saxlayıb geri qaytardığı üçün
> "yoxdur" ilə "null-dur" fərqi görünə bilər — ona görə bu asimmetriya burada
> qəsdən təkrarlanır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int` | ✅ |  | Müştərinin IDsi. |
| `name` | `name` | `string` | ✅ |  | Adı. |
| `phone` | `phone` | `string\|null` |  |  | Telefon nömrəsi. Verilməsə `null` kimi gedir. |
| `customerDiscountType` | `customer_discount_type` | `int\|null` |  |  | Endirim növü. Verilməsə `null` kimi gedir. |
| `address` | `address` | `string\|null` |  |  | Ünvan. Verilməsə payload-dan tamamilə düşür. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `toPayload()` | `array` | Payload forması — `null` qalan iki field-i saxlayaraq. |

## NewOrderProduct

Yeni sifarişdəki bir məhsul sətri.

`meta` sərbəst strukturdur: POS onu olduğu kimi saxlayır və çekə köçürür. Clopos-un
nümunələrində orada məhsulun anlıq surəti və qiyməti olur.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `productId` | `product_id` | `int` | ✅ |  | Məhsulun IDsi. |
| `count` | `count` | `int` | ✅ |  | Miqdar. |
| `productModificators` | `product_modificators` | `?array` |  |  | Seçilmiş modifikatorlar. |
| `meta` | `meta` | `?array` |  |  | Sətrin əlavə məlumatları. |

## NewOrderService

Yeni sifarişin satış kanalı.

Dörd field-in hamısı məcburidir: Clopos sifarişi həm satış növünə, həm də filiala
bağlayır və adları da sifarişin içində saxlayır (sonradan satış növü silinsə belə
sifariş oxunaqlı qalsın deyə).

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `saleTypeId` | `sale_type_id` | `int` | ✅ |  | Satış növünün IDsi. |
| `saleTypeName` | `sale_type_name` | `string` | ✅ |  | Satış növünün adı. |
| `venueId` | `venue_id` | `int` | ✅ |  | Filialın IDsi. |
| `venueName` | `venue_name` | `string` | ✅ |  | Filialın adı. |

## ProductFilter

`listProducts()` üçün filtr toplusu.

Bu, `Data` alt class-ı **deyil**: filtr JSON body-yə yox, query açarlarına çevrilir.
Clopos hər filtri `[ad, dəyər]` cütü kimi gözləyir, ona görə məftildə belə görünür:

```
filters[giftable][0]=giftable&filters[giftable][1]=1
```

Bool field-lər `1`/`0` kimi gedir — Clopos onları sətir `"1"` kimi sənədləşdirib,
lakin ədəd də qəbul edir və Python kitabxanası da ədəd göndərir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `type` | `type` | `list<ProductType>\|null` |  |  | Məhsul növləri. |
| `categoryId` | `categoryId` | `list<int>\|null` |  |  | Kateqoriya IDləri. |
| `stationId` | `stationId` | `list<int>\|null` |  |  | Stansiya IDləri. |
| `tags` | `tags` | `list<int>\|null` |  |  | Teq IDləri. |
| `giftable` | `giftable` | `bool\|null` |  |  | Hədiyyə edilə bilənlər. |
| `discountable` | `discountable` | `bool\|null` |  |  | Endirim tətbiq olunanlar. |
| `inventoryBehavior` | `inventoryBehavior` | `int\|null` |  |  | Anbar davranışı. |
| `haveIngredients` | `haveIngredients` | `bool\|null` |  |  | Resepti olanlar. |
| `soldByPortion` | `soldByPortion` | `bool\|null` |  |  | Porsiya ilə satılanlar. |
| `hasVariants` | `hasVariants` | `bool\|null` |  |  | Variantı olanlar. |
| `hasModifiers` | `hasModifiers` | `bool\|null` |  |  | Modifikator qrupu olanlar. |
| `hasBarcode` | `hasBarcode` | `bool\|null` |  |  | Barkodu olanlar. |
| `hasServiceCharge` | `hasServiceCharge` | `bool\|null` |  |  | Xidmət haqqı tətbiq olunanlar. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `toArray()` | `array` | Clopos-un gözlədiyi `ad => [ad, dəyər]` formasına çevirir. |

## ReceiptPayment

`closeReceipt()`-də çekin bir ödəniş sətri.

Çek bir neçə metodla bağlana bilər (bölünmüş ödəniş) — məbləğlərin cəmi çekin
qalığını örtməlidir.

Məbləğ **sətir** verilir: `float` ötürmək qəpik dəqiqliyini itirə bilər.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int` | ✅ |  | Ödəniş metodunun IDsi. |
| `name` | `name` | `string` | ✅ |  | Metodun adı. |
| `amount` | `amount` | `string` | ✅ |  | Bu metodla ödənilən məbləğ. |

## StopListFilter

`getStopList()` üçün bir aralıq filtri.

Bu, `Data` alt class-ı **deyil**: filtr JSON body-yə yox, query açarlarına çevrilir
(`filters[0][0]=id&filters[0][1][0]=0&filters[0][1][1]=100`).

```php
$client->getStopList(
    new StopListFilter(StopListFilterField::Id, from: 0, to: 100),
);
```

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `by` | `by` | `StopListFilterField` | ✅ |  | Filtrlənən sahə. |
| `from` | `from` | `int` | ✅ |  | Aralığın başlanğıcı. |
| `to` | `to` | `int\|null` |  |  | Aralığın sonu. Verilməsə, yalnız başlanğıc göndərilir. |
