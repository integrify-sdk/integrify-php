# Integrify Core (PHP)

> [!CAUTION]
> Bütün sorğular rəsmi dokumentasiyalara uyğun yazılsalar da, Integrify qeyri-rəsmi API klient-dir.
>
> Integrify is an unofficial library, even though it is based on official documentation.

<!-- markdownlint-disable MD033 -->
<p align="center">
  <a href="https://integrify.mmzeynalli.dev/"><img width="400" src="https://raw.githubusercontent.com/integrify-sdk/integrify-python/main/docs/assets/integrify.png" alt="Integrify"></a>
</p>
<p align="center">
    <em>Integrify API inteqrasiyalarını rahatlaşdıran sorğular kitabxanasıdır. Bu kitabxana, başqa Integrify alt-kitabxanaları üçün "bünövrə" (core) kitabxanadır.</em>
</p>
<!-- markdownlint-enable MD033 -->

---

**Dokumentasiya**: [https://integrify.mmzeynalli.dev](https://integrify.mmzeynalli.dev)

**Kod**: [https://github.com/Integrify-SDK/integrify-php/tree/main/packages/core](https://github.com/Integrify-SDK/integrify-php/tree/main/packages/core)

**Python qarşılığı**: [`integrify-core`](https://github.com/Integrify-SDK/integrify-python/tree/main/packages/core)

---

## Əsas özəlliklər

- Kitabxanadakı bütün sinif və funksiyalar tamamilə dokumentləşdirilib.
- Kitabxanadakı bütün sinif və funksiyalar tipləndirildiyindən, IDE-də avtomatik
  tamamlama və refactoring annotasiyasız işləyir; PHPStan `level: max` təmizdir.
- Sorğuların məntiq axını (flowsu) izah edilib.
- HTTP qatı PSR-18/PSR-17 üzərindədir — Guzzle, Symfony HttpClient və ya istənilən
  digər implementasiya ilə işləyir.
- Bütün xətalar vahid `IntegrifyException` iyerarxiyasındadır.

> [!NOTE]
> PHP-də Python-dakına bənzər async dəstəyi olmadığı üçün bütün klientlər sinxrondur.

---

## Kitabxananın yüklənməsi

```console
composer require integrify/core
```

Bu paket PSR-18 klienti və PSR-17 factory-ləri tələb edir. Layihənizdə yoxdursa:

```console
composer require guzzlehttp/guzzle nyholm/psr7
```

## Nədən ibarətdir

| Sinif | Məqsəd |
| :--- | :--- |
| `Integrify\Client` | İnteqrasiya klientlərinin baza class-ı |
| `Integrify\Response` | Cavab: `status`, `headers`, `body` + DTO-ya çevirmə |
| `Integrify\Environment` | `Test` / `Prod` |
| `Integrify\Http\Transport` | Sorğu göndərən qatın interfeysi |
| `Integrify\Http\HttpTransport` | PSR-18 implementasiyası |
| `Integrify\Http\RecordingTransport` | Testlər və debug üçün implementasiya |
| `Integrify\Http\Request` | Göndəriləcək sorğunun dəyişməz təsviri |
| `Integrify\Dto\Data` | DTO-ların baza class-ı |
| `Integrify\Dto\Attribute\Field` | Field adı və validasiya qaydaları |
| `Integrify\Exception\*` | `IntegrifyException` iyerarxiyası |

## İstifadəsi

### Klient yazmaq

Sorğular **adi, tipli metodlardır** — magic dispatch yoxdur. Baza class yalnız url
qurmağı, header-ləri və `Transport`-a ötürməyi öz üzərinə götürür.

```php
use Integrify\Client;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\Http\HttpTransport;
use Integrify\Http\Transport;

final readonly class PaymentRequest extends Data
{
    public function __construct(
        public int $amount,
        #[Field(name: 'order_id')]
        public string $orderId,
    ) {
    }
}

final readonly class Payment extends Data
{
    public function __construct(
        public ?string $status = null,
        #[Field(name: 'transaction_id')]
        public ?string $transactionId = null,
    ) {
    }
}

final class MyClient extends Client
{
    public function __construct(?Transport $transport = null)
    {
        parent::__construct($transport ?? new HttpTransport(), 'https://api.example.com');
    }

    /**
     * Ödənişin başladılması.
     *
     * **POST** `/pay`
     */
    public function pay(int $amount, string $orderId): Payment
    {
        return $this->post('/pay', new PaymentRequest($amount, $orderId))->to(Payment::class);
    }

    /**
     * **GET** `/payments/{id}`
     */
    public function payment(string $id): Payment
    {
        return $this->get($this->uri('/payments/{id}', ['id' => $id]))->to(Payment::class);
    }

    /**
     * @return list<Payment>
     */
    public function listPayments(int $page = 1): array
    {
        return $this->get('/payments', ['page' => $page])->toList(Payment::class);
    }

    protected function defaultHeaders(): array
    {
        return [...parent::defaultHeaders(), 'Authorization' => 'Bearer ...'];
    }
}
```

Baza class-ın verdiyi köməkçilər:

| Metod | Nə edir |
| :--- | :--- |
| `get()` / `post()` / `put()` / `delete()` | Sorğu göndərir |
| `send()` | Ümumi forma (metod, path, body, query, header) |
| `uri($template, $params)` | `{ad}` şablonunu **rawurlencode** ilə doldurur |
| `listBody(iterable $items)` | DTO siyahısını kök səviyyəli JSON array-ə çevirir |
| `objectBody(array $body)` | Boş massivi JSON **obyekti** (`{}`) kimi göndərir |
| `defaultHeaders()` | Hər sorğuya əlavə olunan header-lər (auth, dil) |
| `mergeHeaders()` | Header-ləri böyük-kiçik hərf fərqi olmadan birləşdirir |
| `assertSameHost()` | Mütləq url-in host yoxlaması (genişləndirilə bilər) |

### Url qurmaq

Dəyişən path hissələri **həmişə** `uri()` şablonundan keçirilməlidir:

```php
$this->uri('/payments/{id}', ['id' => $id]);   // ✅ kodlanır
'/payments/' . $id;                             // ❌
```

Sətir birləşdirməsində `$id` içindəki `?`, `#` və `/` url-in mənasını dəyişir:
`42?admin=1` öz query parametrini sorğuya əlavə edir, `../../internal` isə baza
path-dən çıxır. Bu səbəbdən path-də `?` və ya `#` görünəndə `InvalidRequest` atılır.

Mütləq url-ə (`https://...`) icazə verilir — səhifələmə linkləri belə gəlir — lakin
yalnız `baseUrl` ilə **eyni host**-a. `defaultHeaders()` adətən API açarı daşıyır, və
yad host-a sorğu onu sızdırardı. Başqa host lazımdırsa `assertSameHost()`-u override edin.

### Sorğu cavabı

Yuxarıdakı sorğuların (və ya istənilən sorğunun) cavab formatı `Response` class-ıdır:

```php
final readonly class Response
{
    public int $status;
    /** Cavab sorğusunun status kodu */

    public array $headers;
    /** Cavab sorğusunun header-ləri */

    public string $body;
    /** Cavab sorğusunun xam body-si */
}
```

| Metod | Nə edir |
| :--- | :--- |
| `isSuccessful()` | Status kodu 400-dən kiçikdirsə `true` (Python-dakı `ok`) |
| `header($name)` | Header-in ilk dəyəri |
| `headerValues($name)` | Header-in bütün dəyərləri (`Set-Cookie`, `Link` və s.) |
| `toArray()` | Body-ni massiv kimi. JSON deyilsə **exception yox**, boş massiv |
| `to($dto)` | Body-ni DTO-ya çevirir |
| `toList($dto)` | Kök səviyyəli JSON array-i DTO siyahısına çevirir |
| `Response::json($payload, $status)` | Testlər üçün hazır cavab qurur |

Cavab JSON deyilsə (məs., gateway xətası zamanı HTML səhifə və ya boş body),
`toArray()` boş massiv qaytarır ki, sorğu axını crash olmasın; xətanı `status` və
`isSuccessful()` ilə öyrənmək olar. `toList()` isə kök səviyyədə **obyekt** gələndə
`ValidationFailed` atır — açarları səssizcə atmaq data itkisidir.

## DTO-lar

DTO-lar `readonly` class kimi, konstruktorda promote olunmuş property-lərlə yazılır.
API-nin gözlədiyi ad və validasiya qaydaları `#[Field]` atributu ilə verilir:

```php
final readonly class GoodsItem extends Data
{
    public function __construct(
        #[Field(name: 'namE_OF_GOODS', maxLength: 500)]
        public string $name,
        #[Field(name: 'goodsList', of: GoodsItem::class, maxItems: 40)]
        public array $items = [],
    ) {
    }
}
```

`#[Field]` parametrləri: `name`, `of` (massivin element tipi), `maxLength`, `minLength`,
`pattern`, `min`, `max`, `minItems`, `maxItems`.

`of` yalnız `Data` alt class-ı və ya enum ola bilər — səhv ad `LogicException` verir,
çünki əks halda bütün element validasiyası səssizcə söndürülərdi.

| Metod | Nə edir |
| :--- | :--- |
| `Data::from(array)` | Massivdən DTO qurur və validasiya edir |
| `Data::wrap(array\|Data)` | Massiv və ya hazır DTO qəbul edir |
| `Data::wrapAll(iterable)` | Siyahını DTO siyahısına çevirir |
| `$dto->toArray(skipNull:, only:)` | API-nin gözlədiyi massiv |
| `Data::properties()` | Property adları, elan olunma sırasında |

Həm PHP-dəki property adı, həm də API-dəki ad qəbul olunur. Validasiya xətaları bir yerə
toplanıb tək `ValidationFailed`-də qaytarılır:

```php
try {
    GoodsItem::from(['name' => str_repeat('a', 600)]);
} catch (ValidationFailed $failure) {
    $failure->errors; // ['name' => 'must be at most 500 characters, got 600']
}
```

Detallar:

- Uzunluqlar **simvol** sayına görə ölçülür (bayt yox).
- Enum-lar avtomatik cast olunur; tanınmayan dəyər `ValidationFailed` verir, `TypeError` yox.
- Nested DTO-lar və DTO siyahıları rekursiv qurulur; xəta mesajı elementin indeksini
  göstərir (`items: [2]: ...`).
- Ədədlər səssizcə kəsilmir: kəsr dəyər, `NAN`/`INF` və `int` diapazonundan kənar
  ədədlər rədd edilir.
- Union tiplərində (`Circle|Square`) hər variant ayrıca yoxlanılır.

## Xətalar

| Exception | Nə vaxt |
| :--- | :--- |
| `ValidationFailed` | DTO validasiyası uğursuz (sorğu göndərilmir) |
| `InvalidRequest` | Sorğu qurularkən klient kodundakı səhv: kodlanmamış path, doldurulmamış `{placeholder}`, yad host |
| `RequestFailed` | Şəbəkə xətası, və ya 400+ status kodu — `->request` və `->response` üzərində detallar |
| `MissingConfiguration` | Məcburi environment dəyişəni yoxdur |

Hamısı `Integrify\Exception\IntegrifyException` interfeysini implement edir, ona görə
kitabxanadan gələn hər şeyi bir yerdə tutmaq mümkündür.

Metodların qaytarış tipləri konkret olduğu üçün uğursuzluğu qaytarış dəyəri ilə bildirmək
mümkün deyil — ona görə HTTP səviyyəsindəki xətalar exception kimi qalxır. Servisin
özünün HTTP 200 ilə bildirdiyi məntiqi xətalar buraya daxil deyil; onlar cavab DTO-sunda
modelləşdirilir.

## Mühit

```php
Environment::parse($_ENV['APP_ENV'] ?? null);          // tanınmayan dəyər -> Test
Environment::parse($_ENV['APP_ENV'] ?? null, strict: true);  // tanınmayan dəyər -> exception
```

Tanınan yazılışlar: `prod`, `production`, `live` → `Prod`; `test`, `testing`, `sandbox`,
`dev`, `development`, `local` → `Test`.

## Testlər

`RecordingTransport` sorğuları göndərmir, yadda saxlayır — Python-dakı `dry` rejiminin
qarşılığıdır, lakin klientin qaytarış tiplərini pozmadan:

```php
$transport = new RecordingTransport();
$transport->queue(Response::json(['status' => 'ok']));

$client = new MyClient($transport);
$client->pay(100, '12345678');

$transport->lastRequest()->uri;
$transport->lastRequest()->headers;
$transport->lastRequest()->body;
$transport->count();
```

Xəta axınını yoxlamaq üçün növbəyə `RequestFailed` də qoymaq olar.

`RecordingTransport` real transport-la eyni müqaviləni saxlayır:

- 400+ status kodlu cavab növbəyə qoyulsa, `RequestFailed` atılır;
- JSON-a çevrilə bilməyən payload (`INF`, `NAN`, yararsız UTF-8) rədd edilir;
- növbə boşdursa və fallback verilməyibsə, gözlənilməyən sorğu exception qaldırır —
  "bir dəfə çağırılır" testi dörd çağırışda keçməsin deyə. Bunu istəmirsinizsə:
  `new RecordingTransport(Response::json([]))`.

## Logging

`HttpTransport` konstruktorda PSR-3 logger qəbul edir; verilməsə heç nə yazılmır.
Şəbəkə xətası və 400+ cavablar `error` səviyyəsində yazılır.

```php
new HttpTransport(logger: $monolog);
```
