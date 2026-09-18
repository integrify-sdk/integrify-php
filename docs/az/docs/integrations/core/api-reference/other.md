---
title: Core · Digər obyektlər
---

# Digər obyektlər

## Field

DTO property-sinin API-dəki adını və validasiya qaydalarını təyin edir.

```php
final readonly class GoodsItem extends Data
{
    public function __construct(
        #[Field(name: 'namE_OF_GOODS', maxLength: 500)]
        public string $name,

        #[Field(name: 'goodsList', of: GoodsItem::class, max: 40)]
        public array $items = [],
    ) {
    }
}
```

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `name` | `name` | `string\|null` |  |  | API-nin gözlədiyi field adı. Verilməsə, property adı istifadə olunur. |
| `of` | `of` | `class-string\|null` |  |  | `array` tipli field-in element tipi (DTO və ya enum). |
| `maxLength` | `maxLength` | `int\|null` |  |  | Sətir üçün maksimum simvol sayı. |
| `minLength` | `minLength` | `int\|null` |  |  | Sətir üçün minimum simvol sayı. |
| `pattern` | `pattern` | `string\|null` |  |  | Sətir üçün PCRE şablonu (delimiter-lərlə birlikdə). Diqqət: PCRE-də `$` sətrin sonundakı bir `\n`-i də qəbul edir, ona görə validasiya şablonlarında `$` yox, `\z` istifadə edin. |
| `min` | `min` | `int\|float\|null` |  |  | Ədəd üçün minimum dəyər. |
| `max` | `max` | `int\|float\|null` |  |  | Ədəd üçün maksimum dəyər, və ya massiv üçün maksimum element sayı. |
| `minItems` | `minItems` | `int\|null` |  |  | Massiv üçün minimum element sayı. |
| `maxItems` | `maxItems` | `int\|null` |  |  | Massiv üçün maksimum element sayı. |

## CastFailed

Tək bir property-nin çevirmə/validasiya xətası.

`Mapper` bu xətaları toplayıb sonda bir `ValidationFailed`-ə çevirir, ona görə
kitabxanadan kənara çıxmır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `message` | `message` | `string` |  |  |  |
| `code` | `code` | `int` |  |  |  |
| `previous` | `previous` | `?Throwable` |  |  |  |

## Data

Bütün sorğu və cavab DTO-larının baza class-ı.

DTO-lar `readonly` class kimi, konstruktorda promote olunmuş property-lərlə yazılır;
API-dəki ad və validasiya qaydaları `#[Field]` atributu ilə verilir:

```php
final readonly class GoodsItem extends Data
{
    public function __construct(
        #[Field(name: 'namE_OF_GOODS', maxLength: 500)]
        public string $name,
        #[Field(name: 'goodS_ID')]
        public ?string $categoryId = null,
    ) {
    }
}
```

Həm PHP-dəki property adı, həm də API-dəki ad qəbul olunur, ona görə
`GoodsItem::from(['name' => 'Kitab'])` və `GoodsItem::from(['namE_OF_GOODS' => 'Kitab'])`
eyni nəticəni verir.

_Field-i yoxdur._

## Mapper

Massiv <-> DTO çevirməsini və validasiyanı həyata keçirən reflection mapper-i.

DTO-ların metadata-sı class başına bir dəfə hesablanıb keşlənir.

_Field-i yoxdur._

## PropertyMeta

Bir DTO property-si haqqında reflection-dan çıxarılmış metadata.

Mapper-in isti yolunda olduğu üçün tez-tez lazım olan qərarlar (tək tipdirmi,
qaydası varmı, adı API adı ilə eynidirmi) burada əvvəlcədən hesablanır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `property` | `property` | `string` | ✅ |  | PHP-dəki property adı. |
| `name` | `name` | `string` | ✅ |  | API-dəki field adı. |
| `types` | `types` | `list<string>` | ✅ |  | Qəbul edilən tiplər (`null`-suz). |
| `singleType` | `singleType` | `string\|null` | ✅ |  | Yalnız bir tip varsa, həmin tip. |
| `nullable` | `nullable` | `bool` | ✅ |  | `null` qəbul edirmi. |
| `hasDefault` | `hasDefault` | `bool` | ✅ |  | Default dəyəri varmı. |
| `default` | `default` | `mixed` | ✅ |  | Default dəyər. |
| `validates` | `validates` | `bool` | ✅ |  | Ən azı bir validasiya qaydası varmı. |
| `nameMatchesProperty` | `nameMatchesProperty` | `bool` | ✅ |  | API adı property adı ilə eynidirmi. |
| `field` | `field` | `Field` | ✅ |  | Atributun özü. |

## HttpTransport

PSR-18 üzərində qurulmuş transport.

Klient və factory-lər verilməzsə, `php-http/discovery` vasitəsilə tətbiqdə
mövcud olan implementasiyalar tapılır (Guzzle, Symfony HttpClient və s.).
Hər şey lazy qurulur: obyekt yaradılarkən yox, ilk sorğuda.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `client` | `client` | `?ClientInterface` |  |  |  |
| `requests` | `requests` | `?RequestFactoryInterface` |  |  |  |
| `streams` | `streams` | `?StreamFactoryInterface` |  |  |  |
| `logger` | `logger` | `?LoggerInterface` |  |  |  |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `send()` | `Response` |  |

## RecordingTransport

Sorğu göndərmədən onları yadda saxlayan transport.

İki işə yarayır:

1. **Testlər** — hazır cavabları növbəyə qoyub göndərilən sorğuları yoxlamaq;
2. **Debug** — real sorğu atmadan hansı url-in, header-lərin və payload-un
   göndəriləcəyini görmək (əvvəlki "dry" rejiminin əvəzi, lakin klientin
   qaytarış tiplərini pozmadan).

```php
$transport = new RecordingTransport();
$transport->queue(Response::json(['code' => 200]));

$client = new EPointClient($config, $transport);
$client->declare($package);

$transport->lastRequest()->uri;   // göndərilən url
$transport->lastRequest()->body;  // göndərilən payload
```

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `fallback` | `fallback` | `Response\|null` |  |  | Növbə boşaldıqdan sonra qaytarılacaq cavab. Verilməsə, gözlənilməyən sorğu **exception** qaldırır: səssizcə `200` qaytarmaq "bir dəfə çağırılır" testinin dörd çağırışda da keçməsinə səbəb olurdu. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `queue()` | `RecordingTransport` | Növbəti sorğunun cavabını növbəyə qoyur. |
| `send()` | `Response` |  |
| `requests()` | `array` | Göndərilmiş bütün sorğular. |
| `lastRequest()` | `Request` | Son göndərilmiş sorğu. |
| `count()` | `int` |  |
| `reset()` | `RecordingTransport` | Yadda saxlanmış sorğuları və növbəni təmizləyir. |

## Request

Göndəriləcək sorğunun dəyişməz (immutable) təsviri.

PSR-7 `RequestInterface`-dən fərqli olaraq body burada hələ də massivdir —
JSON-a çevirmə `HttpTransport`-un işidir. Bu, testlərdə payload-u sətir kimi
deyil, olduğu kimi yoxlamağa imkan verir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `method` | `method` | `string` | ✅ |  | HTTP metodu (`GET`, `POST`, ...). |
| `uri` | `uri` | `string` | ✅ |  | Sorğunun tam url-i. |
| `body` | `body` | `stdClass\|array\|null` |  |  | Body. PHP-də `[]` həm boş obyekt, həm boş siyahıdır; JSON-a `[]` kimi yazılır. Boş **obyekt** göndərmək üçün `new stdClass()` verin (bax: `Client::objectBody()`). |
| `query` | `query` | `array` |  |  | Query parametrləri. |
| `headers` | `headers` | `array` |  |  | Sorğu header-ləri. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `withHeader()` | `Request` | Header əlavə edilmiş yeni sorğu qaytarır. |
| `withHeaders()` | `Request` | Header-lər əlavə edilmiş yeni sorğu qaytarır. |
| `bodyIsList()` | `bool` | Body kök səviyyədə JSON array-dirsə `true`. |

## Response

Servisdən gələn xam cavab.

Klient metodları adətən bunu birbaşa qaytarmır — `to()` / `toList()` ilə konkret
DTO-ya çevirib qaytarırlar. Cavabın özünə (status, header, xam body) yalnız
`RequestFailed` exception-ı vasitəsilə, və ya `Transport` səviyyəsində baxılır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `status` | `status` | `int` | ✅ |  | HTTP status kodu. |
| `headers` | `headers` | `array` | ✅ |  | Cavabın header-ləri. |
| `body` | `body` | `string` | ✅ |  | Xam body mətni. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `isSuccessful()` | `bool` |  |
| `header()` | `?string` | Header-in **ilk** dəyəri. |
| `headerValues()` | `array` | Header-in bütün dəyərləri (təkrarlana bilən header-lər üçün). |
| `toArray()` | `array` | Body-ni massiv kimi qaytarır. |
| `to()` | `Data` | Body-ni verilmiş DTO-ya çevirir. |
| `toList()` | `array` | Kök səviyyəsində array olan body-ni DTO siyahısına çevirir. |
