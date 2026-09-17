# Integrify LSIM (PHP)

> [!CAUTION]
> Bütün sorğular rəsmi dokumentasiyalara uyğun yazılsalar da, Integrify qeyri-rəsmi API klient-dir.
>
> Integrify is an unofficial library, even though it is based on official documentation.

<!-- markdownlint-disable MD033 -->
<p align="center">
  <a href="https://integrify.mmzeynalli.dev/"><img width="400" src="https://raw.githubusercontent.com/integrify-sdk/integrify-python/main/docs/assets/integrify.png" alt="Integrify"></a>
</p>
<p align="center">
    <em>LSIM SMS API-si üçün Integrify inteqrasiyası: tək və toplu SMS göndərilməsi, çatdırılma hesabatları və balans.</em>
</p>
<!-- markdownlint-enable MD033 -->

---

**Dokumentasiya**: [https://integrify.mmzeynalli.dev/integrations/lsim](https://integrify.mmzeynalli.dev/integrations/lsim)

**Kod**: [https://github.com/integrify-sdk/integrify-php/tree/main/packages/lsim](https://github.com/integrify-sdk/integrify-php/tree/main/packages/lsim)

**Python qarşılığı**: [`integrify-lsim`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/lsim)

---

## Kitabxananın yüklənməsi

```console
composer require integrify/lsim
```

Layihənizdə PSR-18 klienti yoxdursa, birini əlavə edin:

```console
composer require guzzlehttp/guzzle nyholm/psr7
```

## İki API, iki klient

LSIM-in tək və toplu SMS API-ləri **fərqli host-lardadır** və ortaq endpoint-ləri yoxdur,
ona görə ayrı klientlərdir:

| Klient            | Host                     | Nə üçün                             |
| :---------------- | :----------------------- | :---------------------------------- |
| `SingleSmsClient` | `https://apps.lsim.az`   | Bir nömrəyə SMS, status, balans     |
| `BulkSmsClient`   | `https://www.sendsms.az` | Minlərlə nömrəyə SMS, toplu hesabat |

Kimlik məlumatları hər ikisində eynidir və `LsimConfig` ilə ötürülür.

## Konfiqurasiya

```php
use Integrify\Lsim\LsimConfig;

$config = new LsimConfig(
    login: 'my-login',
    password: 'secret',
    senderName: 'MyShop',
);

// və ya environment-dən:
$config = LsimConfig::fromEnvironment();
```

| Dəyişən            | Məcburi | Təsvir                                |
| :----------------- | :-----: | :------------------------------------ |
| `LSIM_LOGIN`       |    ✅    | LSIM logini                           |
| `LSIM_PASSWORD`    |    ✅    | LSIM parolu                           |
| `LSIM_SENDER_NAME` |    —    | Göndərən adı; sorğuda da verilə bilər |

> [!NOTE]
> `fromEnvironment()` **adlandırılmış konstruktordur**, gizli axtarış deyil: environment
> yalnız siz çağıranda oxunur. Klient obyekti qurulduqdan sonra heç bir qlobal state-ə
> baxmır, ona görə testlərdə sıfırlanacaq bir şey yoxdur.

## Tək SMS

```php
use Integrify\Lsim\SingleSmsClient;

$client = new SingleSmsClient($config);

$result = $client->sendSms('994501234567', 'Salam!');

$result->isSuccessful();  // bool
$result->obj;             // transaction id
$result->code();          // SmsCode::Sent, və ya null (tanınmayan kod)
```

| Metod                   | HTTP | Endpoint                   | Qaytarır                        |
| :---------------------- | :--- | :------------------------- | :------------------------------ |
| `sendSms()`             | GET  | `/quicksms/v1/send`        | `SmsResult`                     |
| `scheduleSms()`         | POST | `/quicksms/v1/smssender`   | `SmsPostResult`                 |
| `checkBalance()`        | GET  | `/quicksms/v1/balance`     | `SmsResult` (`obj` = qalan SMS) |
| `checkStatus()`         | GET  | `/quicksms/v1/report`      | `ReportStatus`                  |
| `fetchDeliveryReport()` | POST | `/quicksms/v1/smsreporter` | `DeliveryReport`                |

Unicode simvollar (`ə`, `ş`, `ü`) varsa `unicode: true` verin:

```php
$client->sendSms('994501234567', 'Şəkil göndərildi', unicode: true);
```

Gələcək bir zamana planlaşdırmaq üçün:

```php
$client->scheduleSms(
    '994501234567',
    'Sabahınız xeyir',
    new DateTimeImmutable('2026-05-19 09:00:00'),
);
```

> [!IMPORTANT]
> Parol **heç vaxt göndərilmir**. LSIM `key` adlı imza gözləyir:
> `md5(md5(password) + login + text + msisdn + sender)`. Klient onu özü hesablayır.

Status hesabatı yalnız son **bir həftə** üçün əlçatandır:

```php
$status = $client->checkStatus($result->obj);

$status->isDelivered();   // bool
$status->code();          // SmsCode::Delivered | SmsCode::InQueue | ...
```

## Toplu SMS

```php
use Integrify\Lsim\BulkSmsClient;

$client = new BulkSmsClient($config);

$submission = $client->sendSameMessage(
    controlId: 1,
    msisdns: ['994501234567', '994551234567'],
    message: 'Endirim başladı!',
);

$submission->isSuccessful();  // bool
$submission->taskId;          // hesabat üçün lazımdır
```

| Metod                            | `operation`                | Qaytarır             |
| :------------------------------- | :------------------------- | :------------------- |
| `sendSameMessage()`              | `submit` (`isbulk: true`)  | `BulkSubmission`     |
| `sendDifferentMessages()`        | `submit` (`isbulk: false`) | `BulkSubmission`     |
| `fetchReport()`                  | `report`                   | `BulkReport`         |
| `fetchDetailedReport()`          | `detailedreport`           | `BulkDetailedReport` |
| `fetchDetailedReportWithDates()` | `detailedreportwithdate`   | `BulkDetailedReport` |
| `checkBalance()`                 | `units`                    | `BulkBalance`        |

Hər nömrəyə öz mesajını göndərmək üçün siyahılar **eyni uzunluqda** olmalıdır:

```php
$client->sendDifferentMessages(
    controlId: 2,
    msisdns: ['994501234567', '994551234567'],
    messages: ['Salam Əli', 'Salam Aysel'],
);
```

Uzunluqlar uyğun gəlmirsə `InvalidRequest` atılır. (Python versiyası `zip()` istifadə
edir və artıq elementləri səssizcə atır — bir mesajın heç kimə getməməsi aşkar edilməz
xətadır.)

`controlId` sizin təyin etdiyiniz təkrarlanmayan id-dir; LSIM eyni id ilə ikinci sorğunu
`BulkCode::Duplicate` kimi rədd edir.

Hesabatlar:

```php
$report = $client->fetchReport($submission->taskId);
$report->delivered;    // çatmış SMS sayı
$report->undelivered;  // çatmamış
$report->queue;        // sırada

$detailed = $client->fetchDetailedReport($submission->taskId);

foreach ($detailed->reports as $row) {
    $row->msisdn;        // '994501234567'
    $row->smsStatus();   // SmsStatus::Delivered | ...
    $row->date;          // yalnız `fetchDetailedReportWithDates()` cavabında
}
```

> [!NOTE]
> Hesabatdakı `-1` "LSIM bu sahəni qaytarmadı" deməkdir — sıfır yox.

## Status kodları

`SmsCode` (tək SMS), `BulkCode` (toplu tranzaksiya) və `SmsStatus` (toplu göndərilmədə
hər SMS) enum-ları mövcuddur, lakin **cavab DTO-larında tip kimi istifadə olunmur**.
DTO xam `int` saxlayır, enum isə `code()` / `smsStatus()` ilə əlçatandır:

```php
$result->errorCode;   // int, məs. -102
$result->code();      // SmsCode::WrongNumberFormat, və ya null
```

Səbəb: LSIM sabah yeni kod əlavə edərsə, validasiya sınmamalıdır — sorğu cavabı əlinizdə
qalmalıdır ki, nə baş verdiyini görəsiniz.

## Xətalar

| Exception              | Nə vaxt                                                      |
| :--------------------- | :----------------------------------------------------------- |
| `InvalidRequest`       | Boş nömrə siyahısı, uzunluqları uyğun gəlməyən siyahılar     |
| `RequestFailed`        | Şəbəkə xətası, və ya 400+ status kodu                        |
| `ValidationFailed`     | Cavab gözlənilən formada deyil (məs., gateway HTML qaytarıb) |
| `MissingConfiguration` | `LSIM_LOGIN` və ya `LSIM_PASSWORD` yoxdur                    |

LSIM-in **məntiqi** xətaları (HTTP 200, lakin mənfi `errorCode` və ya sıfırdan fərqli
`responseCode`) exception atmır — `isSuccessful()` ilə yoxlanılır.

## Testlər

Sorğu göndərmədən nəyin göndəriləcəyini yoxlamaq üçün `RecordingTransport`:

```php
use Integrify\Http\RecordingTransport;
use Integrify\Response;

$transport = new RecordingTransport();
$transport->queue(Response::json(['successMessage' => 'ok', 'obj' => 12345]));

$client = new SingleSmsClient($config, $transport);
$client->sendSms('994501234567', 'Salam');

$transport->lastRequest()->uri;    // https://apps.lsim.az/quicksms/v1/send
$transport->lastRequest()->query;  // msisdn, text, login, sender, unicode, key
```

> [!NOTE]
> PHP-də Python-dakına bənzər async dəstəyi olmadığı üçün bu paketdə
> `LSIMSingleSMSAsyncClient` kimi qarşılıq yoxdur.
