# Integrify Azericard (PHP)

[Azericard](https://azericard.com) inteqrasiyası — kart ödənişləri (MPI, 3-D Secure) və
pul köçürmələri (MT).

**Composer**: `integrify/azericard`
**Kod**: [https://github.com/integrify-sdk/integrify-php/tree/main/packages/azericard](https://github.com/integrify-sdk/integrify-php/tree/main/packages/azericard)
**Sənədlər**: [https://integrify-php.mmzeynalli.dev/integrations/azericard/](https://integrify-php.mmzeynalli.dev/integrations/azericard/)
**Python qarşılığı**: [`integrify-azericard`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/azericard)

> [!IMPORTANT]
> **Bu API-nin əksəriyyəti server-server sorğu deyil.** Səkkiz əməliyyatdan beşi heç bir
> HTTP sorğusu atmır: onlar müştərinin brauzerinin Azericard-a **özünün** göndərməli
> olduğu formanı qaytarır. Nəticə sonradan sizin callback URL-inizə gəlir.

## Kitabxananın yüklənməsi

```bash
composer require integrify/azericard
composer require guzzlehttp/guzzle nyholm/psr7
```

`ext-openssl` tələb olunur — MPI sorğuları RSA ilə imzalanır.

## İki sistem, iki host, iki açar

| Sistem | Nə üçün | Host (test / prod) | İmza |
| :--- | :--- | :--- | :--- |
| **MPI** | kart ödənişləri | `testmpi.3dsecure.az` / `mpi.3dsecure.az` | RSA SHA-256 (`P_SIGN`) |
| **MT** | pul köçürmələri | `testmt.azericard.com` / `mt.azericard.com` | MD5 + paylaşılan açar |

> [!WARNING]
> **İki açar var və onlar fərqlidir.** MPI RSA private key (PEM) istəyir, MT isə adi
> sətir açarı. Python kitabxanası ikisini də eyni `AZERICARD_KEY_FILE_PATH`
> dəyişənindən oxuyur, halbuki bir fayl hər ikisi ola bilməz. Burada onlar ayrıdır və
> yalnız istifadə etdiyiniz sistem üçün açar vermək kifayətdir.

## Konfiqurasiya

| Dəyişən | Məcburi | Təsvir |
| :--- | :---: | :--- |
| `AZERICARD_MERCHANT_ID` | ✅ | Terminal IDsi |
| `AZERICARD_KEY_FILE_PATH` | kart üçün | RSA private key faylı (PKCS#1 və ya PKCS#8) |
| `AZERICARD_TRANSFER_KEY` | köçürmə üçün | MT-nin MD5 açarı |
| `AZERICARD_MERCHANT_NAME` | kart üçün | Satıcının adı, maksimum 50 simvol |
| `AZERICARD_MERCHANT_URL` | kart üçün | Satıcının saytı |
| `AZERICARD_CALLBACK_URL` | kart üçün | Nəticənin post olunduğu URL |
| `AZERICARD_MERCHANT_EMAIL` | ❌ | Satıcının email ünvanı |
| `AZERICARD_ENV` | ❌ | `test` və ya `prod` |
| `AZERICARD_INTERFACE_LANG` | ❌ | Dil (default `az`) |

```php
use Integrify\Azericard\AzericardClient;
use Integrify\Azericard\AzericardConfig;

$client = new AzericardClient(AzericardConfig::fromEnvironment());

// Açarı fayldan deyil, birbaşa mətndən vermək də olar:
$config = new AzericardConfig(
    merchantId: 'TERM0001',
    privateKey: AzericardConfig::parsePrivateKey($pem),
    transferKey: $sharedKey,
    merchantName: 'MyShop',
    merchantUrl: 'https://shop.az',
    callbackUrl: 'https://shop.az/callback',
);
```

## Ödəniş axını

```
$client->authorize(...)  ──▶  FormPost  ──▶  brauzer Azericard-a post edir
                                                        │
                            sizin callback URL-iniz  ◀───┘
                                     │
                      $callback->decodeAuth($_POST)  ──▶  AuthCallback
```

```php
$form = $client->authorize(
    amount: 10.5,
    currency: '944',      // AZN
    order: '123456',      // 6–32 rəqəm, gün ərzində unikal
    description: 'Sifariş #123456',
);

echo $form->toHtml();   // özünü göndərən forma
```

Öz şablonunuzu qurmaq istəyirsinizsə:

```php
$form->url;      // https://testmpi.3dsecure.az/cgi-bin/cgi_link
$form->method;   // POST
$form->fields;   // ['AMOUNT' => '10.5', ..., 'P_SIGN' => '...']
```

## Bloklama və tamamlama

`authorize()` default olaraq məbləği **birbaşa silir**. Bloklamaq üçün:

```php
use Integrify\Azericard\Enum\AuthorizationType;
use Integrify\Azericard\Enum\ResponseType;

$form = $client->authorize(10.5, '944', '123456', 'Sifariş', AuthorizationType::Freeze);

// callback gəldikdən sonra — rrn və intRef oradan gəlir:
$client->finalize(10.5, '944', '123456', $data->rrn, $data->internalReference, ResponseType::AcceptPayment);
```

| `ResponseType` | Nə edir |
| :--- | :--- |
| `AcceptPayment` (`21`) | bloklanmış məbləği silir |
| `ReturnPayment` (`22`) | ödənişi geri qaytarır |
| `CancelPayment` (`24`) | bloku azad edir |

## Kart yaddaşı

```php
$form = $client->authorizeAndSaveCard(10.5, '944', '123456', 'Sifariş');
// callback-də `token` gəlir

$form = $client->authorizeWithSavedCard(10.5, '944', '123457', 'Təkrar', $token);
```

## Callback-lər

```php
use Integrify\Azericard\Callback;

$callback = new Callback(AzericardConfig::fromEnvironment());

$data = $callback->decodeAuth($_POST);

if ($data->isSuccessful()) {
    // $data->order, $data->rrn, $data->internalReference, $data->token
}
```

> [!CAUTION]
> **Kart callback-inin imzası yoxlanıla bilmir.** `P_SIGN` Azericard-ın öz açarı ilə
> imzalanıb; yoxlamaq üçün onların **public** açarı lazımdır, sizdə isə yalnız öz
> private açarınız var. Python kitabxanası da yoxlamır. Ona görə bu callback-i tək
> başına "ödəniş oldu" sübutu saymayın — `getTransactionStatus()` ilə təsdiqləyin.

Köçürmə callback-i isə yoxlanılır:

```php
$data = $callback->decodeTransfer($_POST);   // imza uyğun gəlməsə SignatureMismatch
```

## Pul köçürmələri (MT)

```php
$form = $client->startTransfer(
    merchant: 'MyShop',
    srn: 'SRN001',
    amount: 10.5,
    currency: '944',
    receiverCredentials: 'Əli Əliyev',
    redirectLink: 'https://shop.az/done',
);

// callback gəldikdən sonra:
$result = $client->confirmTransfer('MyShop', 'SRN001', 10.5, '944');
// və ya
$result = $client->declineTransfer('MyShop', 'SRN001', 10.5, '944');

$callback->verifyTransferResult($result);   // imzanı yoxlayır
```

## Status sorğusu

```php
$status = $client->getTransactionStatus(AuthorizationType::Direct, '123456');

$status->isSuccessful();
$status->statusMessage;
$status->rrn;
$status->internalReference;
```

Bu endpoint cavabında **boşluqlu, insan üçün yazılmış** açarlar qaytarır
(`"Transaction Status message"`, `"Banks approval code"`) — digər endpoint-lərdəki
`UPPER_SNAKE` deyil. DTO adları olduğu kimi qəbul edir.

## Vaxt damğası

Azericard `TIMESTAMP` field-ini **GMT** olaraq təyin edir, format `YYYYMMDDHHMMSS`.
Bu paket həmişə UTC göndərir:

```php
$client->confirmTransfer('Shop', 'SRN001', 10.5, '944', new DateTimeImmutable('now'));
```

> Python kitabxanası `datetime.now()` — yəni serverin **yerli** vaxtını — göndərir.
> Bakıda bu 4 saat fərq deməkdir və şlüz vaxt fərqinə görə sorğunu rədd edə bilər.

## Xətalar

| Exception | Nə vaxt |
| :--- | :--- |
| `MissingConfiguration` | Açar və ya merchant məlumatı yoxdur; `AZERICARD_ENV` tanınmır |
| `InvalidRequest` | Məbləğ ədəd deyil; imzalama uğursuz; host tanınmır |
| `SignatureMismatch` | Köçürmə callback-inin və ya cavabının imzası uyğun gəlmir |
| `ValidationFailed` | Cavab DTO-ya uyğun gəlmir |
| `RequestFailed` | Şəbəkə xətası, və ya HTTP >= 400 |

Hamısı `Integrify\Exception\IntegrifyException`-i implement edir.

## Testlər

Forma qaytaran metodlar HTTP atmadığı üçün transport lazım deyil:

```php
$form = $client->authorize(10.5, '944', '123456', 'Test');

$form->fields['AMOUNT'];    // '10.5'
$form->fields['P_SIGN'];    // imza
```

İmzanın **nəyin üzərində** hesablandığını yoxlamaq üçün `Signature::macSource()`
açıqdır — belə ki, test açardan asılı olmadan düsturu yoxlaya bilir:

```php
use Integrify\Azericard\Signature;

Signature::macSource(['10.5', '944']);   // '410.53944'
```
