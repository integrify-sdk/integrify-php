# Konfiqurasiya

`fromEnvironment()` adlandırılmış konstruktordur: dəyərlər bir dəfə, obyekt yaradılarkən oxunur — sorğu atılarkən yox.

## EPointConfig

EPoint hesabının açarları və sorğu default-ları.

Dəyişməz value object-dir və klientə konstruktorda ötürülür — qlobal state və ya
sorğu anında environment oxuması yoxdur. `fromEnvironment()` adlandırılmış
konstruktordur, yəni environment yalnız siz istəyəndə oxunur.

```php
$config = new EPointConfig(publicKey: 'i000000001', privateKey: 'secret');
// və ya
$config = EPointConfig::fromEnvironment();
```

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `publicKey` | `publicKey` | `string` | ✅ |  | EPoint-in verdiyi public key. Hər payload-a düşür. |
| `privateKey` | `privateKey` | `string` | ✅ |  | EPoint-in verdiyi private key. **Heç vaxt payload-a düşmür** — yalnız `signature()` hesablamasında istifadə olunur. |
| `language` | `language` | `string` |  |  | Ödəniş səhifəsinin dili (`az`, `en`, `ru`). |
| `successRedirectUrl` | `successRedirectUrl` | `string\|null` |  |  | Uğurlu ödənişdən sonra müştərinin yönləndirildiyi URL. `null` — EPoint dashboard-ındakı dəyər işləyir. |
| `errorRedirectUrl` | `errorRedirectUrl` | `string\|null` |  |  | Uğursuz ödənişdən sonra yönləndirilən URL. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `signature()` | `string` | Payload imzası. |
| `verify()` | `bool` | Gələn imzanın gözlənilənlə eyniliyini **sabit zamanda** yoxlayır. |
