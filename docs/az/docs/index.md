# Integrify (PHP)

Azərbaycan servislərinin API-lərini bir kiçik, ardıcıl klient abstraksiyası arxasında
birləşdirən PHP kitabxanaları ailəsi.

Hər inteqrasiya **ayrıca Composer paketidir**: yalnız istifadə etdiyinizi quraşdırırsınız,
hamısı isə eyni `Integrify\` namespace kökünü və eyni qaydaları paylaşır.

```bash
composer require integrify/epoint
composer require guzzlehttp/guzzle nyholm/psr7
```

## Paketlər

| Paket | Servis |
| :--- | :--- |
| [`integrify/core`](integrations/core/index.md) | Ortaq mexanika: `Client`, `Transport`, `Response`, DTO qatı |
| [`integrify/azericard`](integrations/azericard/index.md) | Azericard — kart ödənişləri (MPI) və pul köçürmələri (MT) |
| [`integrify/clopos`](integrations/clopos/index.md) | Clopos — POS: menyu, müştərilər, sifarişlər, çeklər |
| [`integrify/epoint`](integrations/epoint/index.md) | EPoint — kart ödənişləri |
| [`integrify/kapitalbank`](integrations/kapitalbank/index.md) | Kapital Bank — kart ödənişləri |
| [`integrify/lsim`](integrations/lsim/index.md) | LSIM — SMS |
| [`integrify/postaguvercini`](integrations/postaguvercini/index.md) | Posta Güvercini — SMS |

`integrify/ecustoms` (DGK Carriers V4) **məxfidir** və ayrıca, qapalı repodadır.

## Dörd qayda

Bütün paketlər eyni formadadır, ona görə birini öyrənmək hamısını öyrənmək deməkdir.

**Endpoint-lər adi, tipli metodlardır.** Magic dispatch yoxdur; `$client->pay(...)`
IDE-də görünür, PHPStan onu yoxlayır.

**Konfiqurasiya obyektdir, qlobal vəziyyət deyil.** `fromEnvironment()` adlandırılmış
konstruktordur — dəyərlər bir dəfə, obyekt yaradılarkən oxunur. Testlər heç bir mühit
dəyişəni olmadan işləyir.

**Uğursuzluq exception-dur.** Metodlar konkret tip qaytardığı üçün xətanı qaytarış
dəyəri ilə bildirmək mümkün deyil: şəbəkə problemi və HTTP >= 400 `RequestFailed`
atır. Servisin HTTP 200 ilə bildirdiyi məntiqi xətalar isə cavab DTO-sundadır
(`isSuccessful()`), və `throwOnFailure()` ilə exception-a çevrilir.

**Sorğu göndərmədən yoxlamaq olar.** `RecordingTransport` sorğuları yadda saxlayır və
hazır cavabları qaytarır, yəni HTTP klientini mock etmək lazım deyil:

```php
use Integrify\Http\RecordingTransport;
use Integrify\Response;

$transport = new RecordingTransport();
$client = new EPointClient(EPointConfig::fromEnvironment(), $transport);

$transport->queue(Response::json(['status' => 'success']));

$client->pay(amount: 10.5, orderId: '123');

$transport->lastRequest()->body;   // nə göndərildi
```

## Bu sənədlər haradan gəlir

`API Referansı` səhifələri **mənbədən** qurulur: `composer docs` paketlərin üzərindən
refleksiya ilə keçir və hər DTO-nun property-lərini, onların **məftildəki adlarını**
(`#[Field(name: ...)]`) və qaydalarını cədvələ yazır. Paketlərin giriş səhifəsi isə
README-nin özüdür — mətn iki yerdə saxlanılmır.

Python qarşılığı üçün: [integrify-python](https://github.com/integrify-sdk/integrify-python).
