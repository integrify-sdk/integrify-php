---
title: Clopos · Cavab DTO-ları
---

# Cavab DTO-ları

Bu obyektlər servisdən **gəlir**. Enum-a bənzəyən field-lər sətir saxlanılır və enum ayrıca metodla verilir, ona görə servis yeni dəyər əlavə etdikdə cavab validasiyadan keçməkdə davam edir.

## AuthToken

`authenticate()` cavabı — bir saat yaşayan token.

Token `CloposClient`-in içində saxlanılır və növbəti sorğularda `x-token` header-i
kimi gedir; onu əlinizlə daşımağa ehtiyac yoxdur. Yenidən istifadə üçün (məs.
cache-ə yazmaq) `token` və `expiresAt` açıqdır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `token` | `token` | `string` | ✅ |  | Sorğuları avtorizasiya edən JWT. Brend, filial və inteqrator onun içində kodlanıb — ona görə ayrıca `x-brand` header-i lazım deyil. |
| `tokenType` | `token_type` | `string\|null` |  |  | Token növü (`Bearer`). |
| `expiresIn` | `expires_in` | `int\|null` |  |  | Neçə saniyə sonra bitəcəyi. |
| `expiresAt` | `expires_at` | `int\|null` |  |  | Bitmə vaxtının unix dəyəri. |
| `success` | `success` | `bool\|null` |  |  | Servisin uğur bayrağı. |
| `message` | `message` | `string\|null` |  |  | Servisin mesajı. |

## Balance

Balans hesabı — müştərinin mağaza krediti, ödəniş metodunun hesabı və s.

Məbləğ **sətir** saxlanılır: servis onu JSON ədədi kimi qaytara bilər, `float`-a
çevirmək isə qəpik dəqiqliyini itirərdi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Balansın IDsi. |
| `systemType` | `system_type` | `string\|null` |  |  | Sistem növü. |
| `venueId` | `venue_id` | `int\|null` |  |  | Aid olduğu filial. |
| `name` | `name` | `string\|null` |  |  | Balansın adı. |
| `description` | `description` | `string\|null` |  |  | Təsviri. |
| `type` | `type` | `string\|null` |  |  | Balansın növü. |
| `amount` | `amount` | `string\|null` |  |  | Balansdakı məbləğ. |
| `position` | `position` | `int\|null` |  |  | Sıralamadakı mövqe. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

## CashbackBalance

Keşbek balansı — `Balance`-ın qısa forması, vaxt damğaları olmadan.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `name` | `name` | `string\|null` |  |  | Balansın adı. |
| `type` | `type` | `string\|null` |  |  | Balansın növü. |
| `amount` | `amount` | `string\|null` |  |  | Balansdakı məbləğ. |

## Category

Menyu kateqoriyası.

Kateqoriyalar ağac qurur: `parentId` valideyni göstərir, `children` isə
`includeChildren: true` ilə sorğu atıldıqda dolur.

`lft` və `rgt` — nested set sərhədləridir (`_lft` / `_rgt`). Onlar ağacın
daxili təmsilidir; adətən `parentId` və `children` kifayət edir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Kateqoriyanın IDsi. |
| `name` | `name` | `string\|null` |  |  | Adı. |
| `status` | `status` | `int\|null` |  |  | `1` aktiv, `0` deaktiv. |
| `hidden` | `hidden` | `bool\|null` |  |  | Gizlidirmi. |
| `type` | `type` | `string\|null` |  |  | Kateqoriyanın növü. Enum üçün `type()`. |
| `image` | `image` | `Image\|null` |  |  | Şəkli. |
| `position` | `position` | `int\|null` |  |  | Menyudakı mövqe. |
| `emenuPosition` | `emenu_position` | `int\|null` |  |  | eMenu-dakı mövqe. |
| `meta` | `meta` | `?array` |  |  | Əlavə parametrlər. |
| `emenuHidden` | `emenu_hidden` | `bool\|null` |  |  | eMenu-da gizlidirmi. |
| `code` | `code` | `string\|null` |  |  | Kateqoriyanın kodu. |
| `lft` | `_lft` | `int\|null` |  |  | Nested set sol sərhədi (`_lft`). |
| `rgt` | `_rgt` | `int\|null` |  |  | Nested set sağ sərhədi (`_rgt`). |
| `depth` | `depth` | `int\|null` |  |  | Ağacdakı dərinlik. |
| `parentId` | `parent_id` | `int\|null` |  |  | Valideyn kateqoriya, kökdə `null`. |
| `slug` | `slug` | `string\|null` |  |  | URL-ə uyğun identifikator. |
| `color` | `color` | `string\|null` |  |  | HEX rəng. |
| `properties` | `properties` | `?array` |  |  | Əlavə parametrlər. |
| `media` | `media` | `list<Media>\|null` |  |  | Media faylları. |
| `children` | `children` | `list<Category>\|null` |  |  | Alt kateqoriyalar. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `type()` | `?CategoryType` | `type`-ın enum qarşılığı, tanınmayan dəyər üçün `null`. |

## Customer

Müştəri.

`group`, `balance` və `cashbackBalance` yalnız sorğuda `with` verildikdə dolur —
bax `listCustomers(with: ['group', 'balance'])`.

Məbləğlər **sətir** saxlanılır: servis onları JSON ədədi kimi qaytarır, `float`-a
çevirmək isə qəpik dəqiqliyini itirərdi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Müştərinin IDsi. |
| `venueId` | `venue_id` | `int\|null` |  |  | Aid olduğu filial. |
| `cid` | `cid` | `string\|null` |  |  | POS-dakı unikal identifikator. |
| `groupId` | `group_id` | `int\|null` |  |  | Müştəri qrupunun IDsi. |
| `group` | `group` | `CustomerGroup\|null` |  |  | Qrupun özü (`with[]=group`). |
| `balanceId` | `balance_id` | `int\|null` |  |  | Balansın IDsi. |
| `name` | `name` | `string\|null` |  |  | Adı. |
| `discount` | `discount` | `string\|null` |  |  | Şəxsi endirim. |
| `email` | `email` | `string\|null` |  |  | Email ünvanı. |
| `phone` | `phone` | `string\|null` |  |  | Əsas telefon nömrəsi. |
| `phones` | `phones` | `list<string>\|null` |  |  | Bütün telefon nömrələri. |
| `address` | `address` | `string\|null` |  |  | Əsas ünvan. |
| `addresses` | `addresses` | `list<string>\|null` |  |  | Saxlanılmış ünvanlar. |
| `description` | `description` | `string\|null` |  |  | Qeyd. |
| `addressData` | `address_data` | `list<string>\|null` |  |  | Ünvan məlumatları. |
| `bonusBalanceId` | `bonus_balance_id` | `int\|null` |  |  | Bonus balansının IDsi. |
| `balance` | `balance` | `Balance\|null` |  |  | Mağaza krediti balansı (`with[]=balance`). |
| `cashbackBalanceId` | `cashback_balance_id` | `int\|null` |  |  | Keşbek balansının IDsi. |
| `cashbackBalance` | `cashback_balance` | `CashbackBalance\|null` |  |  | Keşbek balansı (`with[]=cashback_balance`). |
| `spent` | `spent` | `string\|null` |  |  | Ümumi xərclənmiş məbləğ. |
| `totalDiscount` | `total_discount` | `string\|null` |  |  | Ümumi endirim. |
| `totalBonus` | `total_bonus` | `string\|null` |  |  | Ümumi bonus. |
| `receiptCount` | `receipt_count` | `int\|null` |  |  | Çeklərin sayı. |
| `gender` | `gender` | `int\|null` |  |  | Cinsi. Enum üçün `gender()`. |
| `dateOfBirth` | `date_of_birth` | `string\|null` |  |  | Doğum tarixi. |
| `code` | `code` | `string\|null` |  |  | Müştərinin kodu. |
| `source` | `source` | `string\|null` |  |  | Müştərinin mənbəyi. |
| `referenceId` | `reference_id` | `string\|null` |  |  | Xarici sistemdəki identifikator. |
| `phoneVerifiedAt` | `phone_verified_at` | `string\|null` |  |  | Nömrənin təsdiq vaxtı. |
| `status` | `status` | `bool\|null` |  |  | Hesab aktivdirmi. |
| `canUseLoyaltySystem` | `can_use_loyalty_system` | `bool\|null` |  |  | Loyallıq sistemindən istifadə edə bilirmi. |
| `isVerified` | `is_verified` | `bool\|null` |  |  | Təsdiqlənibmi. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `gender()` | `?Gender` | `gender`-in enum qarşılığı, tanınmayan dəyər üçün `null`. |

## CustomerGroup

Müştəri qrupu — qrupa bağlı endirim qaydası ilə.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Qrupun IDsi. |
| `name` | `name` | `string\|null` |  |  | Qrupun adı. |
| `discountType` | `discount_type` | `int\|null` |  |  | Endirimin növü. Enum üçün `discountType()`. |
| `discountValue` | `discount_value` | `int\|null` |  |  | Endirimin dəyəri — `Percentage` üçün faiz, `Fixed` üçün məbləğ. |
| `systemType` | `system_type` | `string\|null` |  |  | Sistem növü. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `discountType()` | `?DiscountType` | `discountType`-ın enum qarşılığı, tanınmayan dəyər üçün `null`. |

## ErrorDetail

Clopos-un xəta cavabındakı bir element.

Xəta body-si həmişə siyahıdır — bir sorğuda bir neçə səbəb ola bilər:

```json
{"success": false, "error": [{"message": "...", "http_code": 404}]}
```

Bütün field-lər opsionaldır: gateway bəzən yalnız `message` qaytarır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `message` | `message` | `string\|null` |  |  | İnsan üçün izah. |
| `type` | `type` | `string\|null` |  |  | Xətanın mənbəyi, məs. `server_side`. |
| `exception` | `exception` | `string\|null` |  |  | Laravel exception-ının adı, məs. `NotFoundHttpException`. |
| `code` | `code` | `int\|null` |  |  | Servisin daxili kodu. |
| `httpCode` | `http_code` | `int\|null` |  |  | Xətaya uyğun HTTP status kodu. |

## Image

Bir şəklin ölçü variantları.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `original` | `original` | `string\|null` |  |  | Orijinal ölçü. |
| `large` | `large` | `string\|null` |  |  | Böyük ölçü. |
| `extraLarge` | `extra_large` | `string\|null` |  |  | Ən böyük ölçü. |
| `thumb` | `thumb` | `string\|null` |  |  | Kiçik önizləmə. |
| `blurHash` | `blur_hash` | `string\|null` |  |  | Yüklənənə qədər göstərilən BlurHash. |

## Media

Obyektə bağlanmış media faylı.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `uuid` | `uuid` | `string\|null` |  |  | Faylın UUIDsi. |
| `mimeType` | `mime_type` | `string\|null` |  |  | MIME tipi. |
| `size` | `size` | `int\|null` |  |  | Ölçü (bayt). |
| `urls` | `urls` | `Image\|null` |  |  | Şəklin ölçü variantları. |
| `blurHash` | `blur_hash` | `string\|null` |  |  | BlurHash. |
| `dimensions` | `dimensions` | `?array` |  |  | Ölçülər (sərbəst struktur). |

## Modifier

Modifikator — yeməyə əlavə olunan seçim, məs. "Acılı".

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Modifikatorun IDsi. |
| `name` | `name` | `string\|null` |  |  | Adı. |
| `price` | `price` | `string\|null` |  |  | Əlavə qiymət. |
| `ingredient` | `ingredient` | `?array` |  |  | Bağlı olduğu inqrediyent (sərbəst struktur). |

## ModifierGroup

Modifikator qrupu — məs. "Acılıq dərəcəsi".

`type` seçim qaydasıdır: `1` tək seçim, `0` çox seçim. Neçə seçim edilə biləcəyini
`minSelect` və `maxSelect` müəyyən edir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Qrupun IDsi. |
| `name` | `name` | `string\|null` |  |  | Qrupun adı. |
| `type` | `type` | `int\|null` |  |  | Seçim qaydası: `1` tək, `0` çox. |
| `minSelect` | `min_select` | `int\|null` |  |  | Minimum seçim sayı. |
| `maxSelect` | `max_select` | `int\|null` |  |  | Maksimum seçim sayı. |
| `modifiers` | `modifiers` | `list<Modifier>\|null` |  |  | Qrupdakı modifikatorlar. |
| `adjustToPortion` | `adjust_to_portion` | `bool\|null` |  |  | Porsiya ölçüsünə uyğunlaşdırılırmı. |
| `status` | `status` | `bool\|null` |  |  | Aktivlik. |
| `meta` | `meta` | `?array` |  |  | Əlavə parametrlər. |
| `pivot` | `pivot` | `?array` |  |  | Məhsulla əlaqənin məlumatları. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

## Order

Sifariş — xarici kanaldan POS-a düşən sənəd.

Sifariş çek (`Receipt`) deyil: POS onu qəbul etdikdən sonra `receiptId` dolur və
pul hərəkəti artıq çekin üzərində gedir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Sifarişin IDsi. |
| `venueId` | `venue_id` | `int\|null` |  |  | Filialın IDsi. |
| `type` | `type` | `string\|null` |  |  | Satış növü. |
| `integration` | `integration` | `string\|null` |  |  | İnteqrasiya kanalı. |
| `integrationUuid` | `integration_uuid` | `string\|null` |  |  | İnteqrasiyadakı UUID. |
| `integrationId` | `integration_id` | `int\|null` |  |  | İnteqrasiyanın IDsi. |
| `customerRefId` | `customer_ref_id` | `string\|null` |  |  | Müştərinin xarici identifikatoru. |
| `integrationStatus` | `integration_status` | `string\|null` |  |  | İnteqrasiyanın vəziyyəti. |
| `integrationResponse` | `integration_response` | `string\|null` |  |  | İnteqrasiyanın cavabı. |
| `customerId` | `customer_id` | `int\|null` |  |  | Müştərinin IDsi. |
| `receiveUserId` | `receive_user_id` | `int\|null` |  |  | Sifarişi qəbul edən istifadəçi. |
| `receiveTerminalId` | `receive_terminal_id` | `int\|null` |  |  | Sifarişin qəbul edildiyi terminal. |
| `status` | `status` | `string\|null` |  |  | Sifarişin vəziyyəti. Enum üçün `status()`. |
| `payload` | `payload` | `OrderPayload\|null` |  |  | Sifarişin tərkibi. |
| `receiptId` | `receipt_id` | `int\|null` |  |  | POS sifarişi qəbul etdikdən sonra bağlanan çek. |
| `paymentStatus` | `payment_status` | `string\|null` |  |  | Ödənişin vəziyyəti. |
| `totalAmount` | `total_amount` | `string\|null` |  |  | Sifarişin ümumi məbləği. |
| `lineItems` | `line_items` | `list<OrderItem>\|null` |  |  | POS-un hesabladığı sətirlər. |
| `saleType` | `sale_type` | `SaleType\|null` |  |  | Satış növünün özü. |
| `paymentMethodId` | `payment_method_id` | `int\|null` |  |  | Ödəniş metodunun IDsi. |
| `paymentMethod` | `payment_method` | `PaymentMethod\|null` |  |  | Ödəniş metodunun özü. |
| `properties` | `properties` | `?array` |  |  | Əlavə parametrlər. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?OrderStatus` | `status`-un enum qarşılığı, tanınmayan dəyər üçün `null`. |

## OrderCustomer

Sifarişin içindəki müştəri — tam `Customer` deyil, sifarişlə birlikdə saxlanılan
anlıq surət.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Müştərinin IDsi. |
| `name` | `name` | `string\|null` |  |  | Adı. |
| `phone` | `phone` | `string\|null` |  |  | Telefon nömrəsi. |
| `address` | `address` | `string\|null` |  |  | Ünvan. |
| `customerDiscountType` | `customer_discount_type` | `int\|null` |  |  | Endirim növü. Enum üçün `customerDiscountType()`. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `customerDiscountType()` | `?DiscountType` | `customerDiscountType`-ın enum qarşılığı, tanınmayan dəyər üçün `null`. |

## OrderItem

Sifarişin sətri — POS tərəfindən hesablanmış miqdar və məbləğ.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Sətrin IDsi. |
| `productId` | `product_id` | `int\|null` |  |  | Məhsulun IDsi. |
| `quantity` | `quantity` | `int\|null` |  |  | Miqdar. |
| `unitPrice` | `unit_price` | `string\|null` |  |  | Vahidin qiyməti. |
| `totalPrice` | `total_price` | `string\|null` |  |  | Sətrin ümumi məbləği. |

## OrderPayload

Sifarişin payload-u — POS-a göndərilən tərkib.

Bu, `createOrder()`-ə verilən `NewOrder` DTO-sunun servisdən qayıdan formasıdır:
eyni struktur, lakin bütün field-lər opsional, çünki POS onları mərhələ-mərhələ
doldurur.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `service` | `service` | `SaleService\|null` |  |  | Satış kanalı. |
| `customer` | `customer` | `OrderCustomer\|null` |  |  | Müştəri. |
| `products` | `products` | `list<OrderProduct>\|null` |  |  | Sifarişdəki məhsullar. |
| `meta` | `meta` | `?array` |  |  | Sifarişin əlavə məlumatları. |
| `customerId` | `customer_id` | `int\|null` |  |  | Müştərinin IDsi. |
| `saleTypeId` | `sale_type_id` | `int\|null` |  |  | Satış növünün IDsi. |
| `payloadUpdatedAt` | `payload_updated_at` | `string\|null` |  |  | Payload-un son dəyişiklik vaxtı. |

## OrderProduct

Sifarişdəki bir məhsul sətri.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `productId` | `product_id` | `int\|null` |  |  | Məhsulun IDsi. |
| `count` | `count` | `int\|null` |  |  | Miqdar. |
| `productModificators` | `product_modificators` | `?array` |  |  | Seçilmiş modifikatorlar. |
| `meta` | `meta` | `OrderProductMeta\|null` |  |  | Qiymət və məhsulun surəti. |

## OrderProductMeta

Sifariş sətrinin meta-sı — qiymət və məhsulun anlıq surəti.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `price` | `price` | `string\|null` |  |  | Satış qiyməti. |
| `orderProduct` | `order_product` | `OrderProductProduct\|null` |  |  | Məhsulun surəti. |

## OrderProductProduct

Sifariş sətrinin meta-sındakı məhsul surəti.

> Servis `product` field-ini ya obyekt, ya da **boş massiv** kimi qaytarır (Laravel
> yüklənməmiş əlaqəni `[]` yazır). Boş massiv burada bütün field-ləri `null` olan
> `Product`-a çevrilir — itən məlumat yoxdur, çünki `[]`-də məlumat yoxdur.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `product` | `product` | `Product\|null` |  |  | Məhsulun özü. |
| `count` | `count` | `int\|null` |  |  | Miqdar. |
| `status` | `status` | `string\|null` |  |  | Sətrin vəziyyəti. |
| `productModificators` | `product_modificators` | `?array` |  |  | Seçilmiş modifikatorlar. |
| `productHash` | `product_hash` | `string\|null` |  |  | Məhsul + modifikator kombinasiyasının hash-i. |

## PaymentMethod

Ödəniş metodu — nağd, kart, bonus və s.

`status` filial başına aktivlikdir: `{"1": 1, "2": 0}` — yəni birinci filialda
aktiv, ikincidə yox.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Metodun IDsi. |
| `name` | `name` | `string\|null` |  |  | Metodun adı. |
| `customerRequired` | `customer_required` | `int\|null` |  |  | Müştəri məcburidirmi (`1` / `0`). |
| `isSystem` | `is_system` | `int\|null` |  |  | Sistem metodudurmu. |
| `balanceId` | `balance_id` | `int\|null` |  |  | Bağlı balansın IDsi. |
| `balance` | `balance` | `Balance\|null` |  |  | Bağlı balansın özü. |
| `service` | `service` | `?array` |  |  | Xarici servis inteqrasiyası. |
| `split` | `split` | `int\|null` |  |  | Bölünmüş ödənişdə istifadə oluna bilirmi. |
| `status` | `status` | `?array` |  |  | Filial IDsi → `0`/`1` aktivlik. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

## Price

Bir qiymət sətri — filiala və ya vaxt intervalına görə.

`from` field-i PHP-də açar sözdür, ona görə property `from` yox, `fromInterval`
adlanır; məftildəki ad `#[Field]`-dədir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `price` | `price` | `string\|null` |  |  | Qiymət. |
| `fromInterval` | `from` | `int\|null` |  |  | Hansı intervaldan etibarən (`TIMER` məhsullar üçün). |
| `venueId` | `venue_id` | `int\|null` |  |  | Hansı filial üçün. |

## PriceList

Qiymət cədvəli — filiala və ya kanala görə alternativ qiymətlər toplusu.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Cədvəlin IDsi. |
| `name` | `name` | `string\|null` |  |  | Cədvəlin adı. |
| `description` | `description` | `string\|null` |  |  | Təsviri. |
| `status` | `status` | `bool\|null` |  |  | Aktivdirmi. |
| `prices` | `prices` | `list<PriceListPrice>\|null` |  |  | Cədvəldəki qiymətlər (`with[]=prices`). |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |

## PriceListPrice

Qiymət cədvəlindəki bir sətir — bir məhsulun həmin cədvəldəki qiyməti.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Sətrin IDsi. |
| `listId` | `list_id` | `int\|null` |  |  | Aid olduğu qiymət cədvəli. |
| `productId` | `product_id` | `int\|null` |  |  | Aid olduğu məhsul. |
| `price` | `price` | `string\|null` |  |  | Qiymət. |
| `product` | `product` | `?array` |  |  | Məhsulun özü (`with[]=product`). |
| `list` | `list` | `?array` |  |  | Cədvəlin özü (`with[]=list`). |

## Product

Məhsul — Clopos-un ən geniş obyekti.

Hansı field-lərin dolu olacağını `type` təyin edir:

| `type` | Dolan field-lər |
| :--- | :--- |
| `GOODS` | `modifications` (variantlar), `modificatorGroups` |
| `DISH` | `recipe`, `modificatorGroups` |
| `PREPARATION` | `recipe` |
| `INGREDIENT` | `packages` |
| `TIMER` | `setting` |

Əlaqəli obyektlər (`taxes`, `media`, `recipe`, `setting` və s.) yalnız sorğuda
`with` verildikdə gəlir — bax `getProduct(1, with: ['recipe', 'taxes'])`.

Məbləğlər **sətir** saxlanılır: servis onları JSON ədədi kimi qaytarır, `float`-a
çevirmək isə qəpik dəqiqliyini itirərdi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Məhsulun IDsi. |
| `cid` | `cid` | `string\|null` |  |  | POS-dakı unikal identifikator. |
| `parentId` | `parent_id` | `int\|null` |  |  | Əsas məhsul (variant üçün). |
| `stationId` | `station_id` | `int\|null` |  |  | Hazırlandığı stansiya. |
| `categoryId` | `category_id` | `int\|null` |  |  | Aid olduğu kateqoriya. |
| `unitId` | `unit_id` | `int\|null` |  |  | Ölçü vahidi. |
| `netOutput` | `net_output` | `int\|null` |  |  | Xalis çıxım. |
| `type` | `type` | `string\|null` |  |  | Məhsulun növü. Enum üçün `type()`. |
| `name` | `name` | `string\|null` |  |  | Adı. |
| `parentName` | `parent_name` | `string\|null` |  |  | Əsas məhsulun adı. |
| `image` | `image` | `Image\|null` |  |  | Şəkli. |
| `position` | `position` | `int\|null` |  |  | Menyudakı mövqe. |
| `description` | `description` | `string\|null` |  |  | Təsviri. |
| `barcode` | `barcode` | `string\|null` |  |  | Barkod. |
| `govCode` | `gov_code` | `string\|null` |  |  | Dövlət kodu. |
| `cost` | `cost` | `string\|null` |  |  | Maya dəyəri. |
| `status` | `status` | `int\|null` |  |  | `1` aktiv, `0` deaktiv. |
| `hidden` | `hidden` | `bool\|null` |  |  | Gizlidirmi. |
| `soldByWeight` | `sold_by_weight` | `bool\|null` |  |  | Çəki ilə satılırmı. |
| `maxAge` | `max_age` | `int\|null` |  |  | Maksimum saxlanma müddəti. |
| `discountable` | `discountable` | `bool\|null` |  |  | Endirim tətbiq olunurmu. |
| `giftable` | `giftable` | `bool\|null` |  |  | Hədiyyə edilə bilirmi. |
| `hasModifications` | `has_modifications` | `bool\|null` |  |  | Variantları varmı. |
| `meta` | `meta` | `?array` |  |  | Əlavə parametrlər. |
| `setting` | `setting` | `TimerSetting\|null` |  |  | `TIMER` məhsul üçün qiymət cədvəli. |
| `modifications` | `modifications` | `list<Variant>\|null` |  |  | `GOODS` məhsulun variantları. |
| `modificatorGroups` | `modificator_groups` | `list<ModifierGroup>\|null` |  |  | Modifikator qrupları. |
| `recipe` | `recipe` | `list<Product>\|null` |  |  | `DISH` və `PREPARATION` üçün resept. |
| `packages` | `packages` | `list<PurchasePackage>\|null` |  |  | `INGREDIENT` üçün alış bağlamaları. |
| `variants` | `variants` | `list<Product>\|null` |  |  | Məhsulun variantları. |
| `eMenuId` | `e_menu_id` | `int\|null` |  |  | eMenu IDsi. |
| `emenuCategoryId` | `emenu_category_id` | `int\|null` |  |  | eMenu-dakı kateqoriya. |
| `emenuPosition` | `emenu_position` | `int\|null` |  |  | eMenu-dakı mövqe. |
| `emenuHidden` | `emenu_hidden` | `bool\|null` |  |  | eMenu-da gizlidirmi. |
| `accountingCategoryId` | `accounting_category_id` | `int\|null` |  |  | Mühasibat kateqoriyası. |
| `price` | `price` | `string\|null` |  |  | Baza qiymət — variantların öz qiyməti ola bilər. |
| `prices` | `prices` | `list<Price>\|null` |  |  | Filiala görə qiymətlər. |
| `costPrice` | `cost_price` | `string\|null` |  |  | Maya qiyməti. |
| `markupRate` | `markup_rate` | `string\|null` |  |  | Əlavə dəyər dərəcəsi. |
| `taxes` | `taxes` | `list<Tax>\|null` |  |  | Tətbiq olunan vergilər. |
| `cookingTime` | `cooking_time` | `int\|null` |  |  | Hazırlanma müddəti. |
| `ignoreServiceCharge` | `ignore_service_charge` | `bool\|null` |  |  | Xidmət haqqından azaddırmı. |
| `inventoryBehavior` | `inventory_behavior` | `int\|null` |  |  | Anbar davranışı. |
| `lowStock` | `low_stock` | `int\|null` |  |  | Az qalıq həddi. |
| `unitWeight` | `unit_weight` | `int\|null` |  |  | Vahidin çəkisi (qram). |
| `nameSlug` | `name_slug` | `string\|null` |  |  | Adın slug-ı. |
| `fullName` | `full_name` | `string\|null` |  |  | Variant məlumatı ilə birlikdə tam ad. |
| `grossMargin` | `gross_margin` | `string\|null` |  |  | Ümumi marja. |
| `slug` | `slug` | `string\|null` |  |  | Slug. |
| `color` | `color` | `string\|null` |  |  | Rəng. |
| `venues` | `venues` | `list<Venue>\|null` |  |  | Satıldığı filiallar. |
| `properties` | `properties` | `?array` |  |  | Əlavə parametrlər. |
| `media` | `media` | `list<Media>\|null` |  |  | Media faylları. |
| `tags` | `tags` | `?array` |  |  | Teqlər (sərbəst struktur). |
| `totalQuantity` | `total_quantity` | `int\|null` |  |  | Ümumi qalıq. |
| `totalCost` | `total_cost` | `string\|null` |  |  | Ümumi maya dəyəri. |
| `averageCost` | `average_cost` | `string\|null` |  |  | Orta maya dəyəri. |
| `openReceiptsCount` | `open_receipts_count` | `int\|null` |  |  | Açıq çeklərin sayı. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `type()` | `?ProductType` | `type`-ın enum qarşılığı, tanınmayan dəyər üçün `null`. |

## PurchasePackage

İnqrediyentin alış bağlaması — məs. "10 ədədlik qutu".

`equal` bağlamanın neçə baza vahidinə bərabər olduğunu göstərir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Bağlamanın IDsi. |
| `name` | `name` | `string\|null` |  |  | Bağlamanın adı. |
| `equal` | `equal` | `int\|null` |  |  | Neçə baza vahidi. |

## Receipt

Çek — POS-dakı satış sənədi.

Çek sifarişdən (`Order`) fərqlidir: sifariş xarici kanaldan gələn tələbdir, çek isə
POS-un öz sənədidir və pul hərəkəti onun üzərindədir.

> Open API v2-də çek **yaratmaq və silmək** endpoint-i yoxdur. Mövcud çeki
> bağlamaq (`closeReceipt()`) və bağlanmış çekin bir neçə field-ini dəyişmək
> (`updateClosedReceipt()`) olar.

Məbləğlər **sətir** saxlanılır: servis onları JSON ədədi kimi qaytarır, `float`-a
çevirmək isə qəpik dəqiqliyini itirərdi.

`totalCost` — yeganə camelCase gələn field-dir (`totalCost`), qalanları
`snake_case`-dir. Bu servisin özünün uyğunsuzluğudur, burada düzəldilmir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Çekin IDsi. |
| `venueId` | `venue_id` | `int\|null` |  |  | Filialın IDsi. |
| `cashShiftCid` | `cash_shift_cid` | `string\|null` |  |  | Kassa növbəsinin UUIDsi. |
| `cid` | `cid` | `string\|null` |  |  | Əməliyyatın UUIDsi. |
| `userId` | `user_id` | `int\|null` |  |  | İstifadəçinin IDsi. |
| `openByUserId` | `open_by_user_id` | `int\|null` |  |  | Çeki açan istifadəçi. |
| `closeByUserId` | `close_by_user_id` | `int\|null` |  |  | Çeki bağlayan istifadəçi. |
| `courierId` | `courier_id` | `int\|null` |  |  | Kuryerin IDsi. |
| `sellerId` | `seller_id` | `int\|null` |  |  | Satıcının IDsi. |
| `terminalId` | `terminal_id` | `int\|null` |  |  | Terminalın IDsi. |
| `source` | `source` | `string\|null` |  |  | Çekin mənbəyi. |
| `closedTerminalId` | `closed_terminal_id` | `int\|null` |  |  | Çekin bağlandığı terminal. |
| `serviceNotificationId` | `service_notification_id` | `int\|null` |  |  | Xidmət bildirişinin IDsi. |
| `tableId` | `table_id` | `int\|null` |  |  | Masanın IDsi. |
| `hallId` | `hall_id` | `int\|null` |  |  | Zalın IDsi. |
| `customerId` | `customer_id` | `int\|null` |  |  | Müştərinin IDsi. |
| `saleTypeId` | `sale_type_id` | `int\|null` |  |  | Satış növünün IDsi. |
| `isReturns` | `is_returns` | `bool\|null` |  |  | Geri qaytarma çekidirmi. |
| `guests` | `guests` | `int\|null` |  |  | Qonaq sayı. |
| `status` | `status` | `int\|null` |  |  | Çekin vəziyyət kodu. |
| `localStatus` | `local_status` | `int\|null` |  |  | Lokal vəziyyət kodu. |
| `lock` | `lock` | `bool\|null` |  |  | Kilidlidirmi. |
| `inventoryStatus` | `inventory_status` | `int\|null` |  |  | Anbar vəziyyəti. |
| `reportStatus` | `report_status` | `int\|null` |  |  | Hesabat vəziyyəti. |
| `meta` | `meta` | `?array` |  |  | Əlavə məlumatlar. |
| `suspicion` | `suspicion` | `int\|null` |  |  | Şübhə səviyyəsi. |
| `printed` | `printed` | `bool\|null` |  |  | Çap olunubmu. |
| `total` | `total` | `string\|null` |  |  | Yığılan ümumi məbləğ. |
| `subtotal` | `subtotal` | `string\|null` |  |  | Düzəlişlərdən əvvəlki məbləğ. |
| `originalSubtotal` | `original_subtotal` | `string\|null` |  |  | İlkin məbləğ. |
| `giftTotal` | `gift_total` | `string\|null` |  |  | Hədiyyələrin məbləği. |
| `totalCost` | `totalCost` | `string\|null` |  |  | Ümumi maya dəyəri (`totalCost`). |
| `paymentMethods` | `payment_methods` | `list<ReceiptPaymentMethod>\|null` |  |  | Ödəniş sətirləri. |
| `fiscalId` | `fiscal_id` | `string\|null` |  |  | Fiskal identifikator. |
| `byCash` | `by_cash` | `string\|null` |  |  | Nağd ödənilən məbləğ. |
| `byCard` | `by_card` | `string\|null` |  |  | Kartla ödənilən məbləğ. |
| `remaining` | `remaining` | `string\|null` |  |  | Qalan borc. |
| `customerDiscountType` | `customer_discount_type` | `int\|null` |  |  | Müştəri endiriminin növü. |
| `discountType` | `discount_type` | `int\|null` |  |  | Endirimin növü. Enum üçün `discountType()`. |
| `discountValue` | `discount_value` | `string\|null` |  |  | Endirimin dəyəri. |
| `discountRate` | `discount_rate` | `string\|null` |  |  | Endirimin dərəcəsi. |
| `rpsDiscount` | `rps_discount` | `string\|null` |  |  | RPS endirimi. |
| `serviceCharge` | `service_charge` | `string\|null` |  |  | Xidmət haqqı. |
| `serviceChargeValue` | `service_charge_value` | `string\|null` |  |  | Xidmət haqqının məbləği. |
| `includedTax` | `i_tax` | `string\|null` |  |  | Daxili vergi (`i_tax`). |
| `deliveryFee` | `delivery_fee` | `string\|null` |  |  | Çatdırılma haqqı. |
| `excludedTax` | `e_tax` | `string\|null` |  |  | Xarici vergi (`e_tax`). |
| `totalTax` | `total_tax` | `string\|null` |  |  | Ümumi vergi. |
| `description` | `description` | `string\|null` |  |  | Qeyd. |
| `address` | `address` | `string\|null` |  |  | Ünvan. |
| `terminalVersion` | `terminal_version` | `string\|null` |  |  | Terminalın versiyası. |
| `loyaltyType` | `loyalty_type` | `int\|null` |  |  | Loyallıq növü. |
| `loyaltyValue` | `loyalty_value` | `string\|null` |  |  | Loyallıq dəyəri. |
| `orderStatus` | `order_status` | `string\|null` |  |  | Sifarişin vəziyyəti. Enum üçün `orderStatus()`. |
| `orderNumber` | `order_number` | `string\|null` |  |  | Sifariş nömrəsi. |
| `receiptProducts` | `receipt_products` | `list<ReceiptProduct>\|null` |  |  | Çekin sətirləri. |
| `terminalUpdatedAt` | `terminal_updated_at` | `string\|null` |  |  | Terminaldakı son dəyişiklik vaxtı. |
| `closedAt` | `closed_at` | `string\|null` |  |  | Bağlanma vaxtı. |
| `shiftDate` | `shift_date` | `string\|null` |  |  | Növbənin tarixi. |
| `giftCount` | `gift_count` | `int\|null` |  |  | Hədiyyə sayı. |
| `totalDiscount` | `total_discount` | `string\|null` |  |  | Ümumi endirim. |
| `properties` | `properties` | `?array` |  |  | Əlavə parametrlər. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `discountType()` | `?DiscountType` | `discountType`-ın enum qarşılığı, tanınmayan dəyər üçün `null`. |
| `orderStatus()` | `?OrderStatus` | `orderStatus`-un enum qarşılığı, tanınmayan dəyər üçün `null`. |

## ReceiptPaymentMethod

Çekin ödəniş sətri — bir metodla ödənilmiş məbləğ.

Bir çek bir neçə metodla ödənilə bilər (bölünmüş ödəniş), ona görə bu siyahıdır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Ödəniş metodunun IDsi. |
| `name` | `name` | `string\|null` |  |  | Metodun adı. |
| `amount` | `amount` | `string\|null` |  |  | Bu metodla ödənilən məbləğ. |

## ReceiptProduct

Çekin bir sətri — satılmış məhsul, qiyməti və endirimləri ilə.

Python-da bu iki class-dır (`ReceiptProductIn` və onu genişləndirən
`ReceiptProduct`). PHP-də `readonly` class-ı yalnız `readonly` class genişləndirə
bilər və promoted property-ni alt class-da yenidən elan etmək olmur, ona görə
burada tək, düz class-dır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Sətrin IDsi. |
| `cid` | `cid` | `string\|null` |  |  | Sətrin POS identifikatoru. |
| `productId` | `product_id` | `int\|null` |  |  | Məhsulun IDsi. |
| `meta` | `meta` | `?array` |  |  | Sətrin əlavə məlumatları. |
| `count` | `count` | `int\|null` |  |  | Miqdar. |
| `portionSize` | `portion_size` | `string\|null` |  |  | Porsiya ölçüsü. |
| `total` | `total` | `string\|null` |  |  | Sətrin ümumi məbləği. |
| `price` | `price` | `string\|null` |  |  | Vahidin qiyməti. |
| `cost` | `cost` | `string\|null` |  |  | Maya dəyəri. |
| `isGift` | `is_gift` | `bool\|null` |  |  | Hədiyyədirmi. |
| `receiptId` | `receipt_id` | `int\|null` |  |  | Aid olduğu çek. |
| `productHash` | `product_hash` | `string\|null` |  |  | Məhsul + modifikator kombinasiyasının hash-i. |
| `preprintCount` | `preprint_count` | `int\|null` |  |  | Ön çap sayı. |
| `stationPrintedCount` | `station_printed_count` | `int\|null` |  |  | Stansiyada çap sayı. |
| `stationAbortedCount` | `station_aborted_count` | `int\|null` |  |  | Stansiyada ləğv sayı. |
| `sellerId` | `seller_id` | `int\|null` |  |  | Satıcının IDsi. |
| `loyaltyType` | `loyalty_type` | `string\|null` |  |  | Loyallıq növü. |
| `loyaltyValue` | `loyalty_value` | `string\|null` |  |  | Loyallıq dəyəri. |
| `discountRate` | `discount_rate` | `string\|null` |  |  | Endirim dərəcəsi. |
| `discountValue` | `discount_value` | `string\|null` |  |  | Endirim dəyəri. |
| `discountType` | `discount_type` | `int\|null` |  |  | Endirim növü. Enum üçün `discountType()`. |
| `totalDiscount` | `total_discount` | `string\|null` |  |  | Ümumi endirim. |
| `subtotal` | `subtotal` | `string\|null` |  |  | Endirimdən əvvəlki məbləğ. |
| `receiptDiscount` | `receipt_discount` | `string\|null` |  |  | Çek səviyyəsindəki endirimin payı. |
| `receiptProductModificators` | `receipt_product_modificators` | `?array` |  |  | Seçilmiş modifikatorlar. |
| `taxes` | `taxes` | `?array` |  |  | Sətrə tətbiq olunan vergilər. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `terminalUpdatedAt` | `terminal_updated_at` | `string\|null` |  |  | Terminaldakı son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `discountType()` | `?DiscountType` | `discountType`-ın enum qarşılığı, tanınmayan dəyər üçün `null`. |

## ReceiptStockOperation

Çekin yaratdığı anbar hərəkəti.

Çek bağlananda satılan məhsulun resepti anbardan silinir; hər silinmə bir
`ReceiptStockOperation` sətridir. `beforeQuantity` və `afterQuantity` hərəkətdən
əvvəlki və sonrakı qalığı göstərir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Hərəkətin IDsi. |
| `receiptId` | `receipt_id` | `int\|null` |  |  | Hərəkəti yaradan çek. |
| `receiptProductId` | `receipt_product_id` | `int\|null` |  |  | Çekin hansı sətri ilə bağlıdır. |
| `productId` | `product_id` | `int\|null` |  |  | Qalığı dəyişən məhsul. |
| `stockId` | `stock_id` | `int\|null` |  |  | Anbar qeydinin IDsi. |
| `storageId` | `storage_id` | `int\|null` |  |  | Anbarın IDsi. |
| `quantity` | `quantity` | `string\|null` |  |  | Silinən (və ya əlavə olunan) miqdar. |
| `beforeQuantity` | `before_quantity` | `string\|null` |  |  | Hərəkətdən əvvəlki qalıq. |
| `afterQuantity` | `after_quantity` | `string\|null` |  |  | Hərəkətdən sonrakı qalıq. |
| `cost` | `cost` | `string\|null` |  |  | Vahidin maya dəyəri. |
| `beforeCost` | `before_cost` | `string\|null` |  |  | Hərəkətdən əvvəlki maya dəyəri. |
| `totalCost` | `total_cost` | `string\|null` |  |  | Hərəkətin ümumi maya dəyəri. |
| `operatedAt` | `operated_at` | `string\|null` |  |  | Hərəkətin vaxtı. |
| `product` | `product` | `?array` |  |  | Məhsulun özü. |
| `stock` | `stock` | `?array` |  |  | Anbar qeydinin özü. |

## SaleService

Sifarişin satış kanalı — hansı filialda, hansı satış növü ilə.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `saleTypeId` | `sale_type_id` | `int\|null` |  |  | Satış növünün IDsi. |
| `saleTypeName` | `sale_type_name` | `string\|null` |  |  | Satış növünün adı. |
| `venueId` | `venue_id` | `int\|null` |  |  | Filialın IDsi. |
| `venueName` | `venue_name` | `string\|null` |  |  | Filialın adı. |

## SaleType

Satış növü — "Zalda", "Çatdırılma", "Özün götür" və s.

`status` filial başına aktivlikdir: `{"1": 1, "2": 0}`.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Satış növünün IDsi. |
| `name` | `name` | `string\|null` |  |  | Adı. |
| `systemType` | `system_type` | `string\|null` |  |  | Sistem növü, məs. `IN`, `DELIVERY`. |
| `status` | `status` | `?array` |  |  | Filial IDsi → `0`/`1` aktivlik. |
| `channel` | `channel` | `string\|null` |  |  | Aid olduğu kanal. |
| `serviceChargeRate` | `service_charge_rate` | `string\|null` |  |  | Xidmət haqqının dərəcəsi. |
| `paymentMethodId` | `payment_method_id` | `int\|null` |  |  | Default ödəniş metodunun IDsi. |
| `position` | `position` | `int\|null` |  |  | Siyahıdakı mövqe. |
| `paymentMethod` | `payment_method` | `PaymentMethod\|null` |  |  | Default ödəniş metodunun özü. |
| `media` | `media` | `list<Media>\|null` |  |  | Media faylları. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

## Station

Stansiya — mətbəx, bar və ya digər hazırlıq nöqtəsi.

Məhsul stansiyaya bağlanır; sifariş verildikdə çek həmin stansiyanın printerinə
düşür.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Stansiyanın IDsi. |
| `name` | `name` | `string\|null` |  |  | Stansiyanın adı. |
| `status` | `status` | `int\|null` |  |  | `1` aktiv, `0` deaktiv. |
| `type` | `type` | `int\|null` |  |  | Stansiyanın növü. |
| `printable` | `printable` | `int\|null` |  |  | Çap oluna bilirmi. |
| `canPrint` | `can_print` | `bool\|null` |  |  | Printerə yönləndirilə bilirmi. |
| `reminderEnabled` | `reminder_enabled` | `bool\|null` |  |  | Xatırlatma bildirişi aktivdirmi. |
| `meta` | `meta` | `?array` |  |  | Əlavə parametrlər. |
| `description` | `description` | `string\|null` |  |  | Təsvir. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

## StopListEntry

Stop-list sətri — qalığı azalmış məhsulun limiti.

`id` məhsulun IDsidir, `limit` isə qalan say. `timestamp` son yenilənmənin unix
vaxtıdır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Məhsulun IDsi. |
| `limit` | `limit` | `int\|null` |  |  | Qalan say. |
| `timestamp` | `timestamp` | `int\|null` |  |  | Son yenilənmənin unix vaxtı. |

## Tax

Məhsula tətbiq olunan vergi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Verginin IDsi. |
| `name` | `name` | `string\|null` |  |  | Verginin adı. |
| `rate` | `rate` | `string\|null` |  |  | Dərəcəsi. |

## TimerSetting

`Timer` məhsulun qiymət cədvəli — vaxta görə satılan xidmətlər üçün.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `interval` | `interval` | `int\|null` |  |  | Qiymət intervalı (dəqiqə). |
| `prices` | `prices` | `list<Price>\|null` |  |  | İntervallara görə qiymətlər. |

## User

POS istifadəçisi — ofisiant, kassir, menecer.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | İstifadəçinin IDsi. |
| `email` | `email` | `string\|null` |  |  | Email ünvanı. |
| `username` | `username` | `string\|null` |  |  | POS-da görünən ad. |
| `firstName` | `first_name` | `string\|null` |  |  | Adı. |
| `lastName` | `last_name` | `string\|null` |  |  | Soyadı. |
| `pin` | `pin` | `string\|null` |  |  | POS PIN kodu. |
| `card` | `card` | `string\|null` |  |  | POS kart nömrəsi. |
| `mobileNumber` | `mobile_number` | `string\|null` |  |  | Mobil nömrə. |
| `owner` | `owner` | `int\|null` |  |  | `1` brendin sahibidirsə. |
| `hide` | `hide` | `int\|null` |  |  | `1` POS seçimində gizlidirsə. |
| `salary` | `salary` | `int\|null` |  |  | Əmək haqqı. |
| `barcode` | `barcode` | `string\|null` |  |  | POS barkodu. |
| `tipMessage` | `tip_message` | `string\|null` |  |  | POS-da göstərilən bəxşiş mesajı. |
| `canReceiveTips` | `can_receive_tips` | `bool\|null` |  |  | Bəxşiş ala bilirmi. |
| `loginAt` | `login_at` | `string\|null` |  |  | Son giriş vaxtı. |
| `status` | `status` | `bool\|null` |  |  | Hesab aktivdirmi. |
| `bonusBalanceId` | `bonus_balance_id` | `int\|null` |  |  | Bonus balansının IDsi. |
| `properties` | `properties` | `?array` |  |  | Əlavə parametrlər. |
| `image` | `image` | `list<string>\|null` |  |  | Şəkil url-ləri. |
| `createdAt` | `created_at` | `string\|null` |  |  | Yaradılma vaxtı. |
| `updatedAt` | `updated_at` | `string\|null` |  |  | Son dəyişiklik vaxtı. |
| `deletedAt` | `deleted_at` | `string\|null` |  |  | Silinmə vaxtı. |

## Variant

`Goods` məhsulun variantı — məs. "0.5 L".

Variant öz qiyməti və barkodu olan ayrıca sətirdir; Clopos-da onun `type`-ı
həmişə `MODIFICATION`-dur.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Variantın IDsi. |
| `parentId` | `parent_id` | `int\|null` |  |  | Əsas məhsulun IDsi. |
| `type` | `type` | `string\|null` |  |  | Növ — həmişə `MODIFICATION`. Enum üçün `type()`. |
| `name` | `name` | `string\|null` |  |  | Variantın adı. |
| `price` | `price` | `string\|null` |  |  | Satış qiyməti. |
| `costPrice` | `cost_price` | `string\|null` |  |  | Maya dəyəri. |
| `barcode` | `barcode` | `string\|null` |  |  | Barkod. |
| `fullName` | `full_name` | `string\|null` |  |  | Tam ad (əsas məhsulla birlikdə). |
| `status` | `status` | `int\|null` |  |  | `1` aktiv, `0` deaktiv. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `type()` | `?ProductType` | `type`-ın enum qarşılığı, tanınmayan dəyər üçün `null`. |

## Venue

Filial (branch) — brendin bir satış nöqtəsi.

Sorğunu konkret filiala yönəltmək üçün `CloposConfig::$venueId` (yəni `x-venue`
header-i) istifadə olunur.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Filialın IDsi. |
| `name` | `name` | `string\|null` |  |  | Filialın adı. |
| `address` | `address` | `string\|null` |  |  | Ünvan. |
| `status` | `status` | `int\|null` |  |  | `1` aktiv, `0` deaktiv. |
| `phone` | `phone` | `string\|null` |  |  | Əlaqə nömrəsi. |
| `email` | `email` | `string\|null` |  |  | Əlaqə email-i. |
| `media` | `media` | `list<string>\|null` |  |  | Media url-ləri. |
