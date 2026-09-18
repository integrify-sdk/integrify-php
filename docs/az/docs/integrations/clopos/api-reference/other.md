# Digər obyektlər

## Page

Səhifələnmiş siyahı — elementlər və Clopos-un zərfindəki meta.

Clopos hər cavabı zərfə bükür: `{"success": true, "data": [...], "total": 120, ...}`.
Bu kitabxananın qaydası zərfi çağırana ötürməməkdir, lakin `total`-ı atmaq
səhifələməni mümkünsüz edərdi. Ona görə `Page` **özü siyahı kimi davranır** —
çağıran heç nəyi açmır:

```php
foreach ($client->listProducts() as $product) {
    echo $product->name;
}

count($page);     // bu səhifədəki element sayı
$page->total;     // serverdəki ümumi say
$page->items;     // list<Product>, lazım olsa
```

`Data` alt class-ı **deyil**: zərf sorğunun nəticəsidir, API-nin obyekti deyil.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `items` | `items` | `list<T>` | ✅ |  | Bu səhifədəki elementlər. |
| `total` | `total` | `int\|null` |  |  | Serverdəki ümumi element sayı. Hər endpoint qaytarmır. |
| `sorts` | `sorts` | `list<string>` |  |  | Sıralamaya icazə verilən field-lər. |
| `message` | `message` | `string\|null` |  |  | Servisin mesajı. |
| `time` | `time` | `int\|null` |  |  | Sorğunun serverdə çəkdiyi vaxt (millisaniyə). |
| `timestamp` | `timestamp` | `string\|null` |  |  | Cavabın ISO 8601 vaxtı. |
| `unix` | `unix` | `int\|null` |  |  | Cavabın unix vaxtı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `count()` | `int` | Bu səhifədəki element sayı — `total` deyil. |
| `getIterator()` | `Traversable` |  |
| `isEmpty()` | `bool` | Səhifə boşdursa `true` — siyahının sonuna çatmağın sadə əlaməti. |
| `first()` | `?Data` | İlk element, yoxdursa `null`. |
