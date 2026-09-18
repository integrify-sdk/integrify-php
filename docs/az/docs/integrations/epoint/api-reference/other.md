---
title: EPoint · Digər obyektlər
---

# Digər obyektlər

## Callback

EPoint callback-lərinin açılması və doğrulanması.

`pay()` yalnız ödənişi **başladır**. Nəticə EPoint dashboard-ında qeyd etdiyiniz
URL-ə `POST` olunur, `application/x-www-form-urlencoded` body ilə:

```
data=eyJzdGF0dXMiOiAic3VjY2VzcyJ9&signature=EG7cnaJteYS6cVuR2aqDvpecQtk=
```

`data` base64-lənmiş JSON-dur, `signature` isə onun `EPOINT_PRIVATE_KEY` ilə
imzasıdır. Tipik istifadə (framework-dən asılı olmayaraq — xam body kifayətdir):

```php
$callback = new Callback(EPointConfig::fromEnvironment());

try {
    $data = $callback->decode($request->getContent());
} catch (SignatureMismatch) {
    return response('', 403);
}

if ($data->isSuccessful()) {
    $order = Order::find($data->orderId);
    // ...
}
```

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `config` | `config` | `EPointConfig` | ✅ |  |  |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `decode()` | `CallbackData` | Xam callback body-sini açır və imzasını yoxlayır. |
| `verify()` | `bool` | Callback-in yalnız imzasını yoxlayır, datanı açmadan. |

## BankCode

Bankın 3 simvollu cavab kodları və onların izahı.

Kodlar bankdan gəlir, EPoint-dən deyil, ona görə siyahı tam deyil — bank sabah
yeni kod qaytara bilər. `describe()` tanımadığı kod üçün `null` qaytarır; cavab
DTO-ları isə **xam kodu** saxlayır (`PaymentResult::$code`), izahı ayrıca verir.

Bu, Python kitabxanasından fərqlənən şüurlu seçimdir: orada validator `code`
field-inin özünü izah mətni ilə əvəz edir, yəni tanınan kodun orijinal dəyəri
itir və `'000'` ilə müqayisə etmək mümkün olmur. Burada ikisi də əlçatandır.

Mətnlər Python kitabxanasından **eynilə** götürülüb ki, iki SDK istifadəçiyə aynı
mesajı göstərsin. Bir qismində orfoqrafik problemlər var (`vaasaittün`,
`dolsazlıqdan`, `oqurlarnı kard`); onları burada düzəltmək iki kitabxananı
ayırardı, ona görə toxunulmayıb.

_Field-i yoxdur._
