# Integrify (PHP)

<!-- markdownlint-disable MD033 -->
<p align="center">
  <a href="https://integrify.mmzeynalli.dev/"><img width="400" src="https://raw.githubusercontent.com/integrify-sdk/integrify-python/main/docs/assets/integrify.png" alt="Integrify"></a>
</p>

<p align="center">
  <a href="https://packagist.org/packages/integrify/core"><img alt="Packagist" src="https://img.shields.io/packagist/v/integrify/core?color=%2334D058&label=packagist"></a>
  <a href="https://www.php.net/"><img alt="PHP version" src="https://img.shields.io/badge/php-%3E%3D8.2-777BB4?logo=php&logoColor=white"></a>
  <a href="https://packagist.org/packages/integrify/core"><img alt="Downloads" src="https://img.shields.io/packagist/dt/integrify/core?color=%2334D058"></a>
  <br>
  <a href="https://www.gnu.org/licenses/mit.en.html"><img alt="License" src="https://img.shields.io/badge/license-MIT-16A34A"></a>
  <a href="https://phpstan.org/"><img alt="PHPStan" src="https://img.shields.io/badge/PHPStan-level%20max-2A2A2A"></a>
  <a href="https://github.com/integrify-sdk/integrify-php"><img alt="Monorepo" src="https://img.shields.io/badge/monorepo-integrations-0F766E"></a>
</p>
<!-- markdownlint-enable MD033 -->

---

Integrify API inteqrasiyalarını rahatlaşdıran PHP kitabxanasıdır. Bu repository bütün
Integrify PHP paketlərini bir monorepo daxilində birləşdirir: paylaşılan core + ayrıca
publish olunan inteqrasiya paketləri.

**English below:** [English section](#english)

## Mündəricat / Table of Contents

- [Integrify (PHP)](#integrify-php)
  - [Mündəricat / Table of Contents](#mündəricat--table-of-contents)
  - [Azərbaycanca](#azərbaycanca)
    - [Dokumentasiya](#dokumentasiya)
    - [Əsas özəlliklər](#əsas-özəlliklər)
    - [Kitabxananın yüklənməsi](#kitabxananın-yüklənməsi)
    - [İstifadəsi](#i̇stifadəsi)
      - [Nümunə](#nümunə)
      - [Sorğu cavabı](#sorğu-cavabı)
      - [Xətalar](#xətalar)
      - [Testlər](#testlər)
    - [Dəstəklənən paketlər](#dəstəklənən-paketlər)
  - [English](#english)
    - [Documentation](#documentation)
    - [Key features](#key-features)
    - [Installation](#installation)
    - [Usage](#usage)
      - [Example](#example)
      - [Response object shape](#response-object-shape)
      - [Errors](#errors)
      - [Tests](#tests)
    - [Supported packages](#supported-packages)
    - [Development](#development)
    - [Troubleshooting](#troubleshooting)

---

## Azərbaycanca

### Dokumentasiya

- Dokumentasiya portalı: [https://integrify.mmzeynalli.dev](https://integrify.mmzeynalli.dev)
- Kod bazası: [https://github.com/integrify-sdk/integrify-php](https://github.com/integrify-sdk/integrify-php)
- Python versiyası: [https://github.com/integrify-sdk/integrify-python](https://github.com/integrify-sdk/integrify-python)

### Əsas özəlliklər

- Hər inteqrasiya ayrıca paketdir, yalnız lazım olanı yükləyib istifadə edirsiniz.
- Paylaşılan `integrify/core` ilə kod təkrarının qarşısı alınır.
- Paketlər birlikdə inkişaf etdirilir, amma ayrı-ayrı publish olunur.
- Composer monorepo sayəsində bütün paketlər eyni mühitdə test/lint edilir.
- **Adi, tipli metodlar** — magic dispatch yoxdur. IDE-də avtomatik tamamlama,
  "go to definition" və refactoring annotasiyasız işləyir.
- **Konkret qaytarış tipləri** — `listGoodsGroups(): list<GoodsGroup>` kimi, generic
  wrapper açmağa ehtiyac yoxdur.
- Kitabxanadakı bütün sinif və funksiyalar tamamilə dokumentləşdirilib.
- Kitabxanadakı bütün sinif və funksiyalar tipləndirilib; PHPStan `level: max` təmizdir.
- Sorğuların çoxunun məntiq axını (flowsu) izah edilib.

> [!NOTE]
> PHP-də Python-dakına bənzər async dəstəyi olmadığı üçün bu kitabxana yalnız sinxron
> klientlər verir. Python-dakı `EPointAsyncRequest` kimi qarşılığı yoxdur.

### Kitabxananın yüklənməsi

```console
composer require integrify/core
```

Layihənizdə PSR-18 klienti yoxdursa, birini əlavə edin:

```console
composer require guzzlehttp/guzzle nyholm/psr7
```

> [!IMPORTANT]
> Paketlər hələ `0.x`-dədir. Composer-in `^` operatoru sıfırdan fərqli **ən soldakı**
> rəqəmi qoruduğu üçün `^0.1` = `>=0.1.0 <0.2.0` deməkdir — yəni yalnız patch
> yeniləmələri. API sabitləşənə qədər `0.2.0` kimi minor release-lər constraint-i
> genişləndirməyi tələb edəcək. Detallar:
> [`.github/workflows/publish.yml`](.github/workflows/publish.yml).

### İstifadəsi

#### Nümunə

Hər inteqrasiya `Client`-dən törəyir və hər endpoint adi, tipli metoddur:

```php
use Integrify\Client;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

final readonly class Payment extends Data
{
    public function __construct(
        #[Field(name: 'order_id', maxLength: 32)]
        public string $orderId,
        #[Field(min: 1)]
        public int $amount,
        public ?string $description = null,
    ) {
    }
}

final class EPointClient extends Client
{
    public function pay(Payment $payment): Payment
    {
        return $this->post('/api/1/request', $payment)->to(Payment::class);
    }

    public function status(string $orderId): Payment
    {
        return $this->get($this->uri('/api/1/get-status/{id}', ['id' => $orderId]))
            ->to(Payment::class);
    }
}

$client = new EPointClient(new HttpTransport(), 'https://epoint.az');

$payment = $client->pay(new Payment(
    orderId: '12345678',
    amount: 100,
    description: 'Ödəniş',
));
```

Dəyişən path hissələri **həmişə** `uri()` şablonundan keçirilməlidir — sətir
birləşdirməsi (`'/get-status/' . $orderId`) kodlanmamış `?`, `#` və `/` simvollarının
url-i dəyişməsinə imkan verir.

#### Sorğu cavabı

Bütün sorğuların cavab formatı `Response` class-ıdır:

```php
final readonly class Response
{
    public int $status;
    /** Cavab sorğusunun status kodu */

    public array $headers;
    /** Cavab sorğusunun header-ləri */

    public string $body;
    /** Cavab sorğusunun xam body-si */

    public function isSuccessful(): bool;         // status < 400
    public function header(string $name): ?string;
    public function headerValues(string $name): array;
    public function toArray(): array;             // JSON deyilsə: []
    public function to(string $dto): Data;        // tək obyekt
    public function toList(string $dto): array;   // kök səviyyəli JSON array
}
```

Python-dakı `ApiResponse.ok` burada `isSuccessful()`, `status_code` isə `status`-dur.
`body` xam mətndir; `toArray()`/`to()`/`toList()` onu oxuyur.

#### Xətalar

Metodlar konkret tip qaytardığı üçün uğursuzluq qaytarış dəyəri ilə bildirilə bilmir —
HTTP səviyyəsindəki xətalar exception kimi qalxır:

| Exception              | Nə vaxt                                                                                                      |
| :--------------------- | :----------------------------------------------------------------------------------------------------------- |
| `ValidationFailed`     | DTO-nun field-ləri qaydalara uyğun deyil (sorğu göndərilmir)                                                 |
| `InvalidRequest`       | Sorğu qurularkən klient kodundakı səhv: path-də kodlanmamış `?`/`#`, doldurulmamış `{placeholder}`, yad host |
| `RequestFailed`        | Şəbəkə xətası, və ya 400-dən böyük status kodu (`->request`, `->response` daşıyır)                           |
| `MissingConfiguration` | Məcburi environment dəyişəni yoxdur                                                                          |

Hamısı `IntegrifyException` interfeysini implement edir:

```php
try {
    $client->pay($payment);
} catch (Integrify\Exception\IntegrifyException $exception) {
    // kitabxanadan gələn hər şey
}
```

#### Testlər

Sorğu göndərmədən nəyin göndəriləcəyini yoxlamaq üçün `RecordingTransport` (Python-dakı
`dry` rejiminin qarşılığı, lakin qaytarış tiplərini pozmadan):

```php
use Integrify\Http\RecordingTransport;
use Integrify\Response;

$transport = new RecordingTransport();
$transport->queue(Response::json(['status' => 'success']));

$client = new EPointClient($transport, 'https://epoint.az');
$client->pay($payment);

$transport->lastRequest()->uri;      // göndərilən url
$transport->lastRequest()->headers;  // göndərilən header-lər
$transport->lastRequest()->body;     // göndərilən payload (massiv)
```

Növbədən artıq sorğu gözlənilmirsə `RecordingTransport` exception qaldırır — "bir dəfə
çağırılır" testi dörd çağırışda keçməsin deyə. Bunu istəmirsinizsə, konstruktora
fallback cavab verin: `new RecordingTransport(Response::json([]))`.

### Dəstəklənən paketlər

> [!CAUTION]
> Bütün sorğular rəsmi dokumentasiyalara uyğun yazılsalar da, Integrify qeyri-rəsmi
> API klient-dir.
>
> Even though all requests are written according to official documentation, Integrify
> is an unofficial library for these integrations.

| Paket / Package             | Composer                   |                                                     Status                                                      | Python qarşılığı                                                                                                  |
| :-------------------------- | :------------------------- | :-------------------------------------------------------------------------------------------------------------: | :---------------------------------------------------------------------------------------------------------------- |
| [`core`](packages/core)     | `integrify/core`           |                                                        ✅                                                        | [`integrify-core`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/core)                     |
| [`lsim`](packages/lsim)     | `integrify/lsim`           |                                                        ✅                                                        | [`integrify-lsim`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/lsim)                     |
| [`epoint`](packages/epoint) | `integrify/epoint`         |                                                        ✅                                                        | [`integrify-epoint`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/epoint)                 |
| KapitalBank                 | `integrify/kapitalbank`    | ![loading](https://raw.githubusercontent.com/integrify-sdk/integrify-python/main/docs/assets/spinner-solid.svg) | [`integrify-kapitalbank`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/kapitalbank)       |
| Posta Güvercini             | `integrify/postaguvercini` | ![loading](https://raw.githubusercontent.com/integrify-sdk/integrify-python/main/docs/assets/spinner-solid.svg) | [`integrify-postaguvercini`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/postaguvercini) |
| Azericard                   | `integrify/azericard`      | ![loading](https://raw.githubusercontent.com/integrify-sdk/integrify-python/main/docs/assets/spinner-solid.svg) | [`integrify-azericard`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/azericard)           |
| Clopos                      | `integrify/clopos`         | ![loading](https://raw.githubusercontent.com/integrify-sdk/integrify-python/main/docs/assets/spinner-solid.svg) | [`integrify-clopos`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/clopos)                 |
| ECustoms (məxfi)            | —                          | ![loading](https://raw.githubusercontent.com/integrify-sdk/integrify-python/main/docs/assets/spinner-solid.svg) | [`integrify-ecustoms`](https://github.com/integrify-sdk/integrify-ecustoms)                                       |

✅ = publish olunub · ![loading](https://raw.githubusercontent.com/integrify-sdk/integrify-python/main/docs/assets/spinner-solid.svg) = planlaşdırılır

Yeni paket əlavə etmək üçün [`CLAUDE.md`](CLAUDE.md), publish prosesi üçün
[`.github/workflows/publish.yml`](.github/workflows/publish.yml) faylına baxın.

---

## English

Integrify is a PHP toolkit for API integrations with Azerbaijani services. This
repository is the monorepo for the Integrify PHP package family: a shared core plus
independently published integration packages.

It shares design goals and the exact wire protocol with
[`integrify-python`](https://github.com/integrify-sdk/integrify-python), but it is **not
a transliteration of it**. The PHP API is written for PHP: real typed methods, `readonly`
DTOs, constructor injection, exceptions. Where the two disagree on shape, PHP idiom wins
— only the bytes on the wire have to match.

### Documentation

- Project docs portal: [https://integrify.mmzeynalli.dev](https://integrify.mmzeynalli.dev)
- Code repository: [https://github.com/integrify-sdk/integrify-php](https://github.com/integrify-sdk/integrify-php)

| Document                                             | Purpose                                                            |
| :--------------------------------------------------- | :----------------------------------------------------------------- |
| [`PHP-PRIMER.md`](PHP-PRIMER.md)                     | This codebase explained for developers who know Python but not PHP |
| [`CLAUDE.md`](CLAUDE.md)                             | Internal conventions                                               |
| [`packages/core/README.md`](packages/core/README.md) | The core package in detail                                         |

### Key features

- Each integration is installed independently, so users install only what they need.
- A shared `integrify/core` avoids duplicated base logic.
- Packages are developed together but released independently.
- The Composer monorepo keeps linting and tests unified across packages.
- **Real, typed methods** — no magic dispatch. Autocomplete, go-to-definition and
  refactoring work without annotations.
- **Concrete return types** rather than a generic response wrapper you have to unwrap.
- **`readonly` DTOs** built with named arguments; the API's inconsistent field names are
  hidden behind attributes.
- **Constructor injection** — configuration is an immutable value object, so there is no
  global state to reset between tests.
- **PSR-18 / PSR-17** HTTP layer, swappable via the `Transport` interface.
- **One exception hierarchy** — everything the library throws is an `IntegrifyException`.
- Everything is documented and type hinted; PHPStan runs at `level: max`.

PHP has no async story comparable to Python's, so this library ships sync clients only.

### Installation

```console
composer require integrify/core
composer require guzzlehttp/guzzle nyholm/psr7   # if you have no PSR-18 client yet
```

> [!IMPORTANT]
> The packages are still `0.x`. Composer's caret preserves the left-most non-zero digit,
> so `^0.1` means `>=0.1.0 <0.2.0` — patch updates only. Until the API settles, a minor
> release such as `0.2.0` will require consumers to widen their constraint.

### Usage

#### Example

```php
$client = new EPointClient(new HttpTransport(), 'https://epoint.az');

$payment = $client->pay(new Payment(
    orderId: '12345678',
    amount: 100,
    description: 'Payment',
));
```

Dynamic path segments always go through the `uri()` template so they are encoded:

```php
$this->get($this->uri('/api/1/get-status/{id}', ['id' => $orderId]));
```

String concatenation is refused when the result would contain `?` or `#`, because that
is almost always an unencoded user value smuggling query parameters into the URL.

#### Response object shape

```php
final readonly class Response
{
    public int $status;      // HTTP status code
    public array $headers;   // response headers
    public string $body;     // raw body

    public function isSuccessful(): bool;          // status < 400
    public function header(string $name): ?string;
    public function headerValues(string $name): array;
    public function toArray(): array;              // [] when the body is not JSON
    public function to(string $dto): Data;
    public function toList(string $dto): array;
}
```

Python's `ApiResponse.ok` is `isSuccessful()` here, and `status_code` is `status`.

#### Errors

| Exception              | When                                                                                                     |
| :--------------------- | :------------------------------------------------------------------------------------------------------- |
| `ValidationFailed`     | DTO validation failed; nothing was sent                                                                  |
| `InvalidRequest`       | The request could not be built: unencoded `?`/`#` in a path, an unfilled `{placeholder}`, a foreign host |
| `RequestFailed`        | Network failure, or HTTP >= 400 — carries `->request` and `->response`                                   |
| `MissingConfiguration` | A required environment variable is absent                                                                |

All of them implement `IntegrifyException`.

#### Tests

```php
$transport = new RecordingTransport();
$transport->queue(Response::json(['status' => 'success']));

$client = new EPointClient($transport, 'https://epoint.az');
$client->pay($payment);

$transport->lastRequest()->uri;
$transport->lastRequest()->body;
```

An unqueued request raises, so a test asserting "we call the API once" cannot quietly
pass when the code calls it four times. Pass a fallback response to the constructor to
opt out.

### Supported packages

See the table in the [Azerbaijani section](#dəstəklənən-paketlər) — it is the same list.

### Development

```console
composer install         # dependencies
composer packages        # list the packages and their mirrors
composer sync-packages   # rewrite the root autoload map after adding a package
composer format          # fix code style (PHP-CS-Fixer)
composer lint            # check code style
composer type-check      # static analysis (PHPStan, level max)
composer test            # run the test suite (PHPUnit)
composer coverage        # tests + coverage report (needs Xdebug or PCOV)
composer all             # check-packages + format + type-check + test
```

#### Pre-commit hooks

The same [pre-commit](https://pre-commit.com) setup as `integrify-python`, with
`composer` scripts in place of `just` tasks, so the hooks and CI can never disagree:

```console
pip install pre-commit
pre-commit install
pre-commit install --hook-type pre-push
```

On **commit**: markup checks (XML, YAML, JSON), `check-packages`, `format` on the staged
PHP files, and `type-check`. On **push**: `test` and `secure`, which are slower and hit
the network.

```console
pre-commit run --all-files   # run everything once, without committing
```

### Troubleshooting

**`'php-cs-fixer' is not recognized as an internal or external command`** (or the same for
`phpunit` / `phpstan`) — the dev tools are not installed. Composer puts `vendor/bin` on
the PATH when it runs a script, so this means that directory is empty or missing:

```console
composer install
```

Note that `composer install --no-dev` deliberately skips these tools, so `composer lint`,
`composer test` and `composer type-check` will not work after it. Use a plain
`composer install` for development.

To check what is actually installed:

```console
dir vendor\bin          :: Windows
ls vendor/bin           # macOS / Linux
```

**`PHP CS Fixer ... does not support your PHP version`** — your PHP is newer than the
installed fixer. Run `composer update friendsofphp/php-cs-fixer` first; if you need to
run anyway, set `PHP_CS_FIXER_IGNORE_ENV=1`.

> [!CAUTION]
> Integrify is an unofficial API client, even though it is based on official documentation.
