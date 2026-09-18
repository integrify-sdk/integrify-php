# Cavab DTO-ları

Bu obyektlər servisdən **gəlir**. Enum-a bənzəyən field-lər sətir saxlanılır və enum ayrıca metodla verilir, ona görə servis yeni dəyər əlavə etdikdə cavab validasiyadan keçməkdə davam edir.

## BusinessAddress

Merchant-ın qeydiyyat ünvanı.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `country` | `country` | `string\|null` |  |  | Ölkənin adı. |
| `countryA2` | `countryA2` | `string\|null` |  |  | ISO 3166-1 alpha-2 kodu. |
| `countryN3` | `countryN3` | `int\|null` |  |  | ISO 3166-1 numeric kodu. |

## CardAuthentication

Kartın 3-D Secure autentifikasiyasının nəticəsi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `needCvv2` | `needCVV2` | `bool\|null` |  |  | CVV2 tələb olunurmu. |
| `needTds` | `needTds` | `bool\|null` |  |  | 3-D Secure tələb olunurmu. |
| `tranId` | `tranId` | `string\|null` |  |  | Autentifikasiya tranzaksiyasının IDsi. |
| `tdsDsTranId` | `tdsDsTranId` | `string\|null` |  |  | Directory Server tranzaksiya IDsi. |
| `timestamp` | `timestamp` | `string\|null` |  |  | Autentifikasiyanın vaxtı. |
| `tdsProtocolVer` | `tdsProtocolVer` | `string\|null` |  |  | 3-D Secure protokolunun versiyası. |
| `eci` | `eci` | `string\|null` |  |  | Electronic Commerce Indicator. |
| `tdsARes` | `tdsARes` | `string\|null` |  |  | Authentication Response. |

## CardDetails

Kartın məlumatları.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `expiration` | `expiration` | `string\|null` |  |  | Kartın bitmə tarixi. |
| `brand` | `brand` | `string\|null` |  |  | Ödəniş sistemi (`VISA`, `MC`). |
| `authentication` | `authentication` | `CardAuthentication\|null` |  |  | 3-D Secure nəticəsi. |
| `issuerRid` | `issuerRid` | `string\|null` |  |  | Emitent bankın identifikatoru. |

## ConsumerDevice

Ödənişi edən cihaz.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `browser` | `browser` | `ConsumerDeviceBrowser\|null` |  |  | Brauzerin məlumatları. |

## ConsumerDeviceBrowser

Ödənişi edən brauzer haqqında bankın topladığı məlumat.

3-D Secure risk qiymətləndirməsi üçün toplanır. `*Replaced` field-ləri brauzerin
bildirdiyi dəyərin dəyişdirilmiş görünüb-görünmədiyini bildirir — fırıldaq
əlaməti ola bilər.

Bütün field-lər nullable-dir: bank hansının gələcəyinə zəmanət vermir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `userAgent` | `userAgent` | `string\|null` |  |  | Brauzerin `User-Agent` sətri. |
| `colorDepth` | `colorDepth` | `int\|null` |  |  | Ekranın rəng dərinliyi. |
| `pixelRatio` | `pixelRatio` | `float\|null` |  |  | Piksel nisbəti. |
| `language` | `language` | `string\|null` |  |  | Brauzerin dili. |
| `tzOffset` | `tzOffset` | `int\|null` |  |  | Saat qurşağının fərqi (dəqiqə). |
| `localStorage` | `localStorage` | `bool\|null` |  |  | `localStorage` mövcuddurmu. |
| `languageReplaced` | `languageReplaced` | `bool\|null` |  |  | Dil dəyəri dəyişdirilib görünürmü. |
| `resolutionReplaced` | `resolutionReplaced` | `bool\|null` |  |  | Ekran ölçüsü dəyişdirilib görünürmü. |
| `osReplaced` | `osReplaced` | `bool\|null` |  |  | Əməliyyat sistemi dəyişdirilib görünürmü. |
| `browserReplaced` | `browserReplaced` | `bool\|null` |  |  | Brauzer dəyişdirilib görünürmü. |
| `screenW` | `screenW` | `int\|null` |  |  | Ekranın eni. |
| `screenH` | `screenH` | `int\|null` |  |  | Ekranın hündürlüyü. |
| `screenAvailW` | `screenAvailW` | `int\|null` |  |  | Əlçatan ekran eni. |
| `screenAvailH` | `screenAvailH` | `int\|null` |  |  | Əlçatan ekran hündürlüyü. |
| `platform` | `platform` | `string\|null` |  |  | Platforma. |
| `acceptHeader` | `acceptHeader` | `string\|null` |  |  | `Accept` header-i. |
| `ip` | `ip` | `string\|null` |  |  | Müştərinin IP ünvanı. |
| `refUrl` | `refUrl` | `string\|null` |  |  | Yönləndirən səhifə. |
| `javaEnabled` | `javaEnabled` | `bool\|null` |  |  | Java aktivdirmi. |
| `jsEnabled` | `jsEnabled` | `bool\|null` |  |  | JavaScript aktivdirmi. |

## CreatedOrder

`/api/order`-a edilən dörd sorğunun da cavabı.

`redirectUrl()` müştərinin yönləndirilməli olduğu tam ünvanı qurur — bankın özü
hazır link qaytarmır, `hppUrl`, `id` və `password`-dan yığmaq lazımdır.

`password` sifarişə aid birdəfəlik sirrdir və sonrakı `linkCardToken()` /
`processPaymentWithSavedCard()` sorğularında lazım olur. Merchant parolu ilə
qarışdırmayın.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int` | ✅ |  | Sifariş IDsi. |
| `password` | `password` | `string` | ✅ |  | Sifarişin birdəfəlik parolu. |
| `hppUrl` | `hppUrl` | `string` | ✅ |  | Hosted Payment Page-in baza ünvanı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `redirectUrl()` | `string` | Müştərinin yönləndiriləcəyi tam ünvan. |

## DetailedOrderInformation

`getDetailedOrderInformation()` cavabı — sifarişin tam vəziyyəti.

Bu, bankın verdiyi ən geniş cavabdır: kart, 3-D Secure, müştərinin brauzeri,
merchant və sifariş növünün bütün icazələri. Qısa forma üçün
[`OrderInformation`](#orderinformation).

Məbləğ field-ləri **sətir** saxlanılır: bank onları JSON ədədi kimi qaytarır,
`float`-a çevirmək isə qəpik dəqiqliyini itirə bilər.

`authorizedChargeAmount` və `clearedChargeAmount` fərqlidir: birincisi kartda
bloklanmış, ikincisi faktiki silinmiş məbləğdir. İkisinin fərqi hələ clearing
gözləyən məbləğdir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Sifariş IDsi. |
| `status` | `status` | `string\|null` |  |  | Sifarişin vəziyyəti. Enum üçün `status()`. |
| `prevStatus` | `prevStatus` | `string\|null` |  |  | Əvvəlki vəziyyət. |
| `lastStatusLogin` | `lastStatusLogin` | `string\|null` |  |  | Vəziyyəti sonuncu dəyişən istifadəçi. |
| `amount` | `amount` | `string\|null` |  |  | Sifarişin məbləği. |
| `currency` | `currency` | `string\|null` |  |  | Məzənnə. |
| `hppUrl` | `hppUrl` | `string\|null` |  |  | Hosted Payment Page-in ünvanı. |
| `hppRedirectUrl` | `hppRedirectUrl` | `string\|null` |  |  | Ödənişdən sonra qayıdılan URL. |
| `password` | `password` | `string\|null` |  |  | Sifarişin birdəfəlik parolu. |
| `description` | `description` | `string\|null` |  |  | Sifarişin təsviri. |
| `language` | `language` | `string\|null` |  |  | Ödəniş səhifəsinin dili. |
| `createTime` | `createTime` | `string\|null` |  |  | Yaradılma vaxtı. |
| `finishTime` | `finishTime` | `string\|null` |  |  | Bitmə vaxtı. |
| `srcAmount` | `srcAmount` | `string\|null` |  |  | Mənbə məbləği. |
| `srcAmountFull` | `srcAmountFull` | `string\|null` |  |  | Mənbə məbləği (tam). |
| `srcCurrency` | `srcCurrency` | `string\|null` |  |  | Mənbə məzənnəsi. |
| `dstAmount` | `dstAmount` | `string\|null` |  |  | Hədəf məbləği. |
| `dstCurrency` | `dstCurrency` | `string\|null` |  |  | Hədəf məzənnəsi. |
| `authorizedChargeAmount` | `authorizedChargeAmount` | `string\|null` |  |  | Bloklanmış məbləğ. |
| `clearedChargeAmount` | `clearedChargeAmount` | `string\|null` |  |  | Faktiki silinmiş məbləğ. |
| `clearedRefundAmount` | `clearedRefundAmount` | `string\|null` |  |  | Geri qaytarılmış məbləğ. |
| `cvv2AuthStatus` | `cvv2AuthStatus` | `string\|null` |  |  | CVV2 yoxlamasının nəticəsi. |
| `tdsV1AuthStatus` | `tdsV1AuthStatus` | `string\|null` |  |  | 3-D Secure v1 nəticəsi. |
| `tdsV2AuthStatus` | `tdsV2AuthStatus` | `string\|null` |  |  | 3-D Secure v2 nəticəsi. |
| `tdsServerUrl` | `tdsServerUrl` | `string\|null` |  |  | 3-D Secure serverinin ünvanı. |
| `initiationEnvKind` | `initiationEnvKind` | `string\|null` |  |  | Sorğunun başladığı mühit. |
| `storedTokens` | `storedTokens` | `list<StoredToken>\|null` |  |  | CoF provayderindəki tokenlər. |
| `srcToken` | `srcToken` | `SrcToken\|null` |  |  | Sifarişə bağlanmış kart tokeni. |
| `consumerDevice` | `consumerDevice` | `ConsumerDevice\|null` |  |  | Müştərinin cihazı. |
| `merchant` | `merchant` | `Merchant\|null` |  |  | Merchant-ın məlumatları. |
| `type` | `type` | `DetailedOrderType\|null` |  |  | Sifariş növünün tam təsviri. |
| `terminal` | `terminal` | `?array` |  |  | Terminalın məlumatları (sərbəst struktur). |
| `reportPubs` | `reportPubs` | `?array` |  |  | Hesabat kanalları (sərbəst struktur). |
| `hppCofCapturePurposes` | `hppCofCapturePurposes` | `list<string>\|null` |  |  | Kartın saxlanma məqsədləri. |
| `custAttrs` | `custAttrs` | `list<string>\|null` |  |  | Merchant-ın əlavə atributları. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?OrderStatus` | `status`-un enum qarşılığı, tanınırsa. |
| `isFullyPaid()` | `bool` | Sifariş tam ödənilibmi. |
| `cardToken()` | `?int` | Sonrakı ödənişlərdə istifadə oluna bilən kart tokeni, varsa. |

## DetailedOrderType

Sifariş növünün tam təsviri — hansı əməliyyatlara icazə verildiyi.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `rid` | `rid` | `string\|null` |  |  | Növün identifikatoru (`Order_SMS`, `Order_DMS`, ...). |
| `title` | `title` | `string\|null` |  |  | Növün adı. |
| `orderClass` | `orderClass` | `string\|null` |  |  | Sifarişin sinfi. |
| `hppTranPhase` | `hppTranPhase` | `string\|null` |  |  | HPP tranzaksiya mərhələsi. |
| `secretLength` | `secretLength` | `int\|null` |  |  | Sifariş parolunun uzunluğu. |
| `allowVoid` | `allowVoid` | `bool\|null` |  |  | Ləğvə icazə verilirmi. |
| `paymentMethods` | `paymentMethods` | `list<string>\|null` |  |  | İcazə verilən ödəniş üsulları. |
| `cardBrands` | `cardBrands` | `list<string>\|null` |  |  | İcazə verilən ödəniş sistemləri. |
| `allowTdsAttempt` | `allowTdsAttempt` | `bool\|null` |  |  | 3-D Secure cəhdinə icazə. |
| `allowTdsCant` | `allowTdsCant` | `bool\|null` |  |  | 3-D Secure mümkün olmadıqda icazə. |
| `allowTdsChallenged` | `allowTdsChallenged` | `bool\|null` |  |  | 3-D Secure challenge-a icazə. |
| `allowSurcharge` | `allowSurcharge` | `bool\|null` |  |  | Əlavə haqqa icazə. |
| `allowCvv2` | `allowCVV2` | `bool\|null` |  |  | CVV2-yə icazə. |
| `allowTranTypes` | `allowTranTypes` | `list<string>\|null` |  |  | İcazə verilən tranzaksiya növləri. |
| `allowTranPhases` | `allowTranPhases` | `list<string>\|null` |  |  | İcazə verilən mərhələlər. |
| `allowAuthKinds` | `allowAuthKinds` | `list<string>\|null` |  |  | İcazə verilən avtorizasiya növləri. |
| `allowCofStoreUsages` | `allowCofStoreUsages` | `list<string>\|null` |  |  | İcazə verilən CoF istifadələri. |

## LinkedCardToken

`linkCardToken()` cavabı — saxlanılmış kartın sifarişə bağlanması.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `status` | `status` | `string\|null` |  |  | Bağlanmanın nəticəsi. |
| `cvv2AuthStatus` | `cvv2AuthStatus` | `string\|null` |  |  | CVV2 yoxlamasının nəticəsi. |
| `tdsV1AuthStatus` | `tdsV1AuthStatus` | `string\|null` |  |  | 3-D Secure v1 nəticəsi. |
| `tdsV2AuthStatus` | `tdsV2AuthStatus` | `string\|null` |  |  | 3-D Secure v2 nəticəsi. |
| `otpAutStatus` | `otpAutStatus` | `string\|null` |  |  | OTP yoxlamasının nəticəsi. |
| `srcToken` | `srcToken` | `SrcToken\|null` |  |  | Bağlanmış token. |

## Merchant

Ödənişi qəbul edən merchant.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Merchant IDsi. |
| `rid` | `rid` | `string\|null` |  |  | Merchant-ın identifikatoru. |
| `title` | `title` | `string\|null` |  |  | Merchant-ın adı. |
| `businessAddress` | `businessAddress` | `BusinessAddress\|null` |  |  | Qeydiyyat ünvanı. |
| `trustConsumerPhone` | `trustConsumerPhone` | `bool\|null` |  |  | Müştərinin telefonuna etibar olunurmu. |

## OrderInformation

`getOrderInformation()` cavabı — sifarişin qısa vəziyyəti.

Tam məlumat üçün `getDetailedOrderInformation()`.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int` | ✅ |  | Sifariş IDsi. |
| `typeRid` | `typeRid` | `string` | ✅ |  | Sifariş növünün identifikatoru. |
| `status` | `status` | `string` | ✅ |  | Sifarişin vəziyyəti. Enum üçün `status()`. |
| `lastStatusLogin` | `lastStatusLogin` | `string` | ✅ |  | Vəziyyəti sonuncu dəyişən istifadəçi. |
| `amount` | `amount` | `string` | ✅ |  | Sifarişin məbləği. **Sətir** saxlanılır: bank onu JSON ədədi kimi qaytarır, `float`-a çevirmək isə qəpik dəqiqliyini itirə bilər. |
| `currency` | `currency` | `string` | ✅ |  | Məzənnə. |
| `createTime` | `createTime` | `string` | ✅ |  | Yaradılma vaxtı. |
| `type` | `type` | `OrderType\|null` |  |  | Sifariş növü. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `status()` | `?OrderStatus` | `status`-un enum qarşılığı, tanınırsa. |
| `isFullyPaid()` | `bool` | Sifariş tam ödənilibmi. |

## OrderType

Sifariş növünün qısa təsviri.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `title` | `title` | `string` | ✅ |  | Növün adı. |

## SrcToken

Sifarişə bağlanmış kart tokeni.

`id` sonrakı ödənişlərdə istifadə olunan token nömrəsidir — `payWithSavedCard()`
məhz bunu gözləyir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Token nömrəsi. |
| `paymentMethod` | `paymentMethod` | `string\|null` |  |  | Ödəniş üsulu. |
| `role` | `role` | `string\|null` |  |  | Tokenin rolu. |
| `status` | `status` | `string\|null` |  |  | Tokenin vəziyyəti. |
| `regTime` | `regTime` | `string\|null` |  |  | Qeydiyyat vaxtı. |
| `entryMode` | `entryMode` | `string\|null` |  |  | Kartın daxil edilmə üsulu. |
| `displayName` | `displayName` | `string\|null` |  |  | Kartın göstərilən adı (maskalanmış nömrə). |
| `card` | `card` | `CardDetails\|null` |  |  | Kartın məlumatları. |

## StoredToken

Card-on-File provayderində saxlanılmış token.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `id` | `id` | `int\|null` |  |  | Token nömrəsi. |
| `cofProviderRid` | `cofProviderRid` | `string\|null` |  |  | CoF provayderinin identifikatoru. |
| `ridByCofp` | `ridBycofp` | `string\|null` |  |  | Provayderin verdiyi identifikator. |

## TransactionMatch

Tranzaksiyanın bank tərəfdəki identifikatorları.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `tranActionId` | `tranActionId` | `string` | ✅ |  | Tranzaksiyanın əməliyyat IDsi. |
| `ridByPmo` | `ridByPmo` | `string` | ✅ |  | PMO tərəfindən verilmiş identifikator. |

## TransactionResult

`exec-tran` tranzaksiyalarının cavabı — refund, tam/yarımçıq ləğv, clearing və
saxlanılmış kartla ödəniş.

`approvalCode` yalnız bəzi tranzaksiyalarda (refund, saxlanılmış kartla ödəniş)
gəlir, ona görə nullable-dir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `match` | `match` | `TransactionMatch\|null` |  |  | Tranzaksiyanın bank identifikatorları. |
| `pmoResultCode` | `pmoResultCode` | `string\|null` |  |  | PMO-nun nəticə kodu. İzahı üçün `resultMessage()`. |
| `approvalCode` | `approvalCode` | `string\|null` |  |  | Bankın təsdiq kodu. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `resultMessage()` | `?string` | PMO kodunun izahı, tanınırsa. |
| `isApproved()` | `bool` | PMO tranzaksiyanı təsdiqlədimi. |
