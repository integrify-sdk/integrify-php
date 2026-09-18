---
title: Kapital Bank · Konfiqurasiya
---

# Konfiqurasiya

`fromEnvironment()` adlandırılmış konstruktordur: dəyərlər bir dəfə, obyekt yaradılarkən oxunur — sorğu atılarkən yox.

## KapitalConfig

Kapital Bank merchant hesabının məlumatları.

Dəyişməz value object-dir və klientə konstruktorda ötürülür — qlobal state və ya
sorğu anında environment oxuması yoxdur. `fromEnvironment()` adlandırılmış
konstruktordur, yəni environment yalnız siz istəyəndə oxunur.

```php
$config = new KapitalConfig(username: 'merchant', password: 'secret');
// və ya
$config = KapitalConfig::fromEnvironment();
```

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `username` | `username` | `string` | ✅ |  | Merchant istifadəçi adı. |
| `password` | `password` | `string` | ✅ |  | Merchant parolu. Basic auth header-ində gedir. |
| `environment` | `environment` | `Environment` |  |  | Test və ya prod şlüzü. |
| `language` | `language` | `string` |  |  | Ödəniş səhifəsinin dili (`az`, `en`, `ru`). |
| `redirectUrl` | `redirectUrl` | `string\|null` |  |  | Ödənişdən sonra müştərinin qayıtdığı URL. `null` — merchant kabinetindəki dəyər işləyir. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `baseUrl()` | `string` | Mühitə uyğun şlüz ünvanı. |
| `authorization()` | `string` | HTTP Basic auth header-inin dəyəri. |
