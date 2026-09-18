# Integrify Posta Güvercini (PHP)

[Posta Güvercini](https://www.poctgoyercini.com) SMS servisinin PHP inteqrasiyası —
tək və çoxlu SMS göndərilməsi, çatdırılma hesabatı və balans sorğusu.

**Composer**: `integrify/postaguvercini`
**Kod**: [https://github.com/integrify-sdk/integrify-php/tree/main/packages/postaguvercini](https://github.com/integrify-sdk/integrify-php/tree/main/packages/postaguvercini)
**Sənədlər**: [https://integrify.mmzeynalli.dev/integrations/postaguvercini](https://integrify.mmzeynalli.dev/integrations/postaguvercini)
**Python qarşılığı**: [`integrify-postaguvercini`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/postaguvercini)

> [!IMPORTANT]
> **Servis uğursuz sorğuya da HTTP 200 qaytarır** — nəticə body-dəki `StatusCode`
> field-indədir. `RequestFailed` atılmaması "göndərildi" demək deyil; hər cavabda
> `isSuccessful()` var və ona baxmaq məcburidir.

> [!WARNING]
> Bu servis **parolu hər sorğunun body-sində** göndərir (`Username` / `Password`
> field-ləri) — header-də deyil, imzalanmır, LSIM-dəki kimi hash-lənmir də. Bu
> inteqrasiyanın sorğu body-ləri log-a düşməməlidir.

## Kitabxananın yüklənməsi

```bash
composer require integrify/postaguvercini
composer require guzzlehttp/guzzle nyholm/psr7
```

## Konfiqurasiya

| Dəyişən | Məcburi | Təsvir |
| :--- | :---: | :--- |
| `POSTA_GUVERCINI_USERNAME` | ✅ | Hesabın istifadəçi adı |
| `POSTA_GUVERCINI_PASSWORD` | ✅ | Hesabın parolu |
| `POSTA_GUVERCINI_ORIGINATOR` | ❌ | Göndərən adı |

```php
use Integrify\PostaGuvercini\PostaGuverciniClient;
use Integrify\PostaGuvercini\PostaGuverciniConfig;

$client = new PostaGuverciniClient(PostaGuverciniConfig::fromEnvironment());

// və ya açıq şəkildə
$client = new PostaGuverciniClient(new PostaGuverciniConfig(
    username: 'my-user',
    password: 'secret',
    originator: 'MyShop',
));
```

## Bir mətn, çox alıcı

```php
$result = $client->sendSms('Salam!', ['994501234567', '994551234567']);

if (!$result->isSuccessful()) {
    // $result->statusCode, $result->status(), $result->statusDescription
    return;
}

$result->messageIds();   // ['1234', '1235'] — status sorğusu üçün
```

## Hər alıcıya öz mətni

```php
use Integrify\PostaGuvercini\Dto\Request\SmsMessage;

$result = $client->sendMessages([
    new SmsMessage('994501234567', 'Salam, Əli'),
    new SmsMessage('994551234567', 'Salam, Aysel'),
]);
```

Nömrə və mətn bir DTO-da saxlanılır, iki paralel siyahı kimi yox: fərqli uzunluqlu
siyahılar səssizcə qısalır və bir mesaj heç kimə getmir.

## Planlaşdırılmış göndərilmə

```php
$client->sendSms(
    'Salam!',
    ['994501234567'],
    sendDate: new DateTimeImmutable('2026-09-18 10:30'),
    expireDate: new DateTimeImmutable('2026-09-18 12:00'),
);
```

Servisin formatı **`YYYYMMDD HH:MM`**-dir — ayırıcı yoxdur, saniyə də yoxdur.
`DateTimeInterface` vermək daha təhlükəsizdir, lakin sətir də qəbul olunur:

```php
$client->sendSms('Salam!', ['994501234567'], sendDate: '20260918 10:30');   // ✅
$client->sendSms('Salam!', ['994501234567'], sendDate: '2026-09-18 10:30'); // InvalidRequest
```

> [!CAUTION]
> Python kitabxanası formatı uyğun gəlməyən sətir üçün səssizcə `None` qaytarır, yəni
> **yazılış səhvi olan bir tarix "indi göndər"ə çevrilir** və planlaşdırılmış SMS
> dərhal gedir. Bu paket belə tarixi `InvalidRequest` ilə rədd edir.

## Kanal

```php
use Integrify\PostaGuvercini\Enum\Channel;

$client->sendSms('Kod: 1234', ['994501234567'], channel: Channel::Otp);   // default
$client->sendSms('Endirim!', $numbers, channel: Channel::Bulk);
```

## Çatdırılma hesabatı

```php
$status = $client->getStatus($result->messageIds());

foreach ($status->statuses() as $message) {
    $message->messageId;
    $message->smsStatusDescription;   // 'Delivered'
    $message->isFinal();              // yekun vəziyyətdirmi
}
```

`isFinalStatus` və `smsCharge` servisdə **sətir** kimi gəlir (`"1"`), bool/int kimi
yox — DTO onları olduğu kimi saxlayır, `isFinal()` isə sətri oxuyur. Tanınmayan dəyər
"hələ yekun deyil" sayılır: yenidən soruşmağa aparır, səhvən "bitdi" deməkdən
təhlükəsizdir.

## Balans

```php
$balance = $client->checkBalance();

$balance->isSuccessful();
$balance->balance();   // int, uğursuz sorğuda null
```

`0` ilə `null` fərqlidir: birincisi "kredit bitib", ikincisi "bilinmir". İkisini
qarışdırmaq balansı olan hesabı bitmiş kimi göstərərdi.

## Sorğular

| Metod | Endpoint | Cavab |
| :--- | :--- | :--- |
| `sendSms()` | `POST /api_json/v1/Sms/Send_1_N` | `SendResult` |
| `sendMessages()` | `POST /api_json/v1/Sms/Send_N_N` | `SendResult` |
| `getStatus()` | `POST /api_json/v1/Sms/Status` | `StatusResult` |
| `checkBalance()` | `POST /api_json/v1/Sms/CreditBalance` | `BalanceResult` |

Bütün field adları məftildə **PascalCase**-dir (`Message`, `Receivers`, `SendDate`,
`StatusCode`), iç-içə obyektlərdə də (`Receiver`, `Message`).

## Status kodları

```php
$result->statusCode;   // xam int — servis yeni kod əlavə etsə də sınmır
$result->status();     // StatusCode|null
```

`StatusCode`-da **22** case var. Python sxemində 26 elan görünür, lakin dördü
təkrarlanan dəyərlərdir (`ALPHANUMBERIC_QUERY_*` `CREDIT_INQUIRY_*` ilə eyni 7020–7050
kodlarını paylaşır); Python təkrarı səssizcə alias-a çevirir, PHP-də isə təkrarlanan
dəyər fatal error-dur.

## Xətalar

| Exception | Nə vaxt |
| :--- | :--- |
| `MissingConfiguration` | `POSTA_GUVERCINI_USERNAME`/`PASSWORD` yoxdur |
| `InvalidRequest` | Boş siyahı, və ya tarix formatı yanlışdır |
| `ValidationFailed` | Cavab DTO-ya uyğun gəlmir |
| `RequestFailed` | Şəbəkə xətası, və ya HTTP >= 400 |

Servisin öz xətaları (`StatusCode != 200`) exception deyil — cavab DTO-sundadır.

## Testlər

```php
use Integrify\Http\RecordingTransport;
use Integrify\Response;

$transport = new RecordingTransport();
$client = new PostaGuverciniClient($config, $transport);

$transport->queue(Response::json([
    'StatusCode' => 200,
    'StatusDescription' => 'Test',
    'Result' => [['MessageId' => '1234', 'Receiver' => '994501234567', 'Charge' => 1]],
]));

$client->sendSms('Salam', ['994501234567']);

$transport->lastRequest()->body;   // göndərilmiş payload
```
