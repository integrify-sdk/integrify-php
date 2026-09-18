# Konfiqurasiya

`fromEnvironment()` adlandırılmış konstruktordur: dəyərlər bir dəfə, obyekt yaradılarkən oxunur — sorğu atılarkən yox.

## LsimConfig

LSIM hesabının məlumatları.

Dəyişməz value object-dir və klientə konstruktorda ötürülür — qlobal state və ya
sorğu anında environment oxuması yoxdur. `fromEnvironment()` adlandırılmış
konstruktordur, yəni environment yalnız siz istəyəndə oxunur.

```php
$config = new LsimConfig(login: 'my-login', password: 'secret', senderName: 'MyShop');
// və ya
$config = LsimConfig::fromEnvironment();
```

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `login` | `login` | `string` | ✅ |  | LSIM logini. |
| `password` | `password` | `string` | ✅ |  | LSIM parolu. Heç vaxt payload-a düşmür — yalnız `key` hash-ının hesablanmasında istifadə olunur. |
| `senderName` | `senderName` | `string` |  |  | LSIM tərəfindən təyin olunmuş göndərən adı. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `signature()` | `string` | Tək SMS sorğuları üçün imza. |
| `balanceSignature()` | `string` | Balans sorğusu üçün imza: `md5(md5(password) + login)`. |
