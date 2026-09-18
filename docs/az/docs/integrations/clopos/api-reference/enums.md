---
title: Clopos · Enum-lar
---

# Enum-lar

## Endpoint

Clopos Open API v2-nin endpoint-ləri.

Yollar baza url-ə nisbətəndir (`https://integrations.clopos.com/open-api/v2/`).
Dəyişən hissə `{id}` şəklindədir və `Client::uri()` tərəfindən doldurulur —
sətir birləşdirməsi ilə deyil, ona görə `id` içindəki `/` və ya `?` url-i dəyişə
bilmir.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `Endpoint::Auth` | `auth` | Token alınması — yeganə açarsız endpoint. |
| `Endpoint::Venues` | `venues` |  |
| `Endpoint::Users` | `users` |  |
| `Endpoint::User` | `users/{id}` |  |
| `Endpoint::Customers` | `customers` |  |
| `Endpoint::Customer` | `customers/{id}` |  |
| `Endpoint::CustomerGroups` | `customer-groups` |  |
| `Endpoint::Categories` | `categories` |  |
| `Endpoint::Category` | `categories/{id}` |  |
| `Endpoint::Stations` | `stations` |  |
| `Endpoint::Station` | `stations/{id}` |  |
| `Endpoint::Products` | `products` |  |
| `Endpoint::Product` | `products/{id}` |  |
| `Endpoint::StopList` | `products/stop-list` |  |
| `Endpoint::SaleTypes` | `sale-types` |  |
| `Endpoint::PaymentMethods` | `payment-methods` |  |
| `Endpoint::Orders` | `orders` |  |
| `Endpoint::Order` | `orders/{id}` |  |
| `Endpoint::Receipts` | `receipts` |  |
| `Endpoint::Receipt` | `receipts/{id}` |  |
| `Endpoint::ReceiptClose` | `receipts/{id}/close` |  |
| `Endpoint::ReceiptStockOperations` | `receipts/{id}/stock-operations` |  |
| `Endpoint::PriceLists` | `price-lists` |  |
| `Endpoint::PriceListPrices` | `price-lists/prices` |  |

## CategoryType

Kateqoriyanın növü.

Clopos-da kateqoriya yalnız menyu bölməsi deyil: eyni ağac həm satılan məhsulları,
həm anbardakı inqrediyentləri, həm də mühasibat maddələrini saxlayır.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `CategoryType::Product` | `PRODUCT` | Satışa çıxan məhsullar. |
| `CategoryType::Ingredient` | `INGREDIENT` | Anbar inqrediyentləri — resept vasitəsilə məhsula daxil olur. |
| `CategoryType::Accounting` | `ACCOUNTING` | Mühasibat maddələri — satılmır, yalnız hesabatda görünür. |

## CustomerFilterField

`listCustomers()` filtrinin sahələri.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `CustomerFilterField::Name` | `name` |  |
| `CustomerFilterField::Phones` | `phones` |  |
| `CustomerFilterField::GroupId` | `group_id` |  |

## CustomerRelation

`listCustomers()` sorğusunda əlavə yüklənə bilən əlaqələr.

Verilməsə, `Customer`-in uyğun field-ləri `null` qalır.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `CustomerRelation::Group` | `group` | `Customer::$group` — müştəri qrupu. |
| `CustomerRelation::Balance` | `balance` | `Customer::$balance` — mağaza krediti balansı. |
| `CustomerRelation::CashbackBalance` | `cashback_balance` | `Customer::$cashbackBalance` — keşbek balansı. |

## DiscountType

Endirimin hesablanma qaydası.

`Percentage` üçün `discountValue` faizdir, `Fixed` üçün isə məbləğ.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `DiscountType::None` | `0` |  |
| `DiscountType::Percentage` | `1` |  |
| `DiscountType::Fixed` | `2` |  |

## Gender

Müştərinin cinsi.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `Gender::Male` | `1` |  |
| `Gender::Female` | `2` |  |

## OrderRelation

`getOrder()` sorğusunda əlavə yüklənə bilən əlaqə.

Clopos burada **bir** dəyər qəbul edir, siyahı yox.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `OrderRelation::ReceiptId` | `receipt:id` | Bağlı çekin yalnız IDsi. |
| `OrderRelation::ServiceNotificationId` | `service_notification_id` |  |
| `OrderRelation::Status` | `status` |  |

## OrderStatus

Sifarişin həyat dövrü.

`updateOrderStatus()` ilə dəyişdirilir; POS-a ötürülən sifariş `Confirmed`
vəziyyətinə keçdikdən sonra ona bir receipt bağlanır.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `OrderStatus::New` | `NEW` |  |
| `OrderStatus::Scheduled` | `SCHEDULED` |  |
| `OrderStatus::InProgress` | `IN_PROGRESS` |  |
| `OrderStatus::Pending` | `PENDING` |  |
| `OrderStatus::Ready` | `READY` |  |
| `OrderStatus::PickedUp` | `PICKED_UP` |  |
| `OrderStatus::Confirmed` | `CONFIRMED` |  |
| `OrderStatus::Completed` | `COMPLETED` |  |
| `OrderStatus::Cancelled` | `CANCELLED` |  |
| `OrderStatus::Received` | `RECEIVED` |  |
| `OrderStatus::Ignore` | `IGNORE` |  |

## ProductRelation

`getProduct()` sorğusunda əlavə yüklənə bilən əlaqələr.

Verilməsə, `Product`-un uyğun field-ləri `null` qalır.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `ProductRelation::Taxes` | `taxes` |  |
| `ProductRelation::Unit` | `unit` |  |
| `ProductRelation::Modifications` | `modifications` |  |
| `ProductRelation::ModificatorGroups` | `modificator_groups` |  |
| `ProductRelation::Recipe` | `recipe` |  |
| `ProductRelation::Packages` | `packages` |  |
| `ProductRelation::Media` | `media` |  |
| `ProductRelation::Tags` | `tags` |  |
| `ProductRelation::Setting` | `setting` |  |

## ProductType

Məhsulun növü.

Növ məhsulun hansı field-lərinin dolu olacağını təyin edir:
`Goods` variantlar (`modifications`), `Dish` resept (`recipe`) və modifikator
qrupları, `Ingredient` alış bağlamaları (`packages`), `Timer` isə vaxta görə
qiymət cədvəli (`setting`) daşıyır.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `ProductType::Goods` | `GOODS` | Hazır mal — variantları ola bilər. |
| `ProductType::Dish` | `DISH` | Yemək — resepti və modifikator qrupları ola bilər. |
| `ProductType::Timer` | `TIMER` | Vaxta görə satılan xidmət (məs. bilyard masası). |
| `ProductType::Preparation` | `PREPARATION` | Yarımfabrikat — özü də reseptdən ibarətdir. |
| `ProductType::Ingredient` | `INGREDIENT` | Anbar inqrediyenti. |
| `ProductType::Modification` | `MODIFICATION` | Variant — `Goods` məhsulun ölçü/çeşid variantı. |

## StopListFilterField

`getStopList()` filtrinin sahələri.

Filtr **aralıq** verir: `from` məcburi, `to` opsionaldır.

| Case | Dəyər | Təsvir |
| :--- | :--- | :--- |
| `StopListFilterField::Id` | `id` | Məhsulun IDsi. |
| `StopListFilterField::Limit` | `limit` | Qalan say. |
| `StopListFilterField::Timestamp` | `timestamp` | Son yenilənmənin unix vaxtı. |
