# Konfiqurasiya

`fromEnvironment()` adlandırılmış konstruktordur: dəyərlər bir dəfə, obyekt yaradılarkən oxunur — sorğu atılarkən yox.

## PostaGuverciniConfig

Posta Güvercini hesabının məlumatları.

Dəyişməz value object-dir və klientə konstruktorda ötürülür — qlobal state və ya
sorğu anında environment oxuması yoxdur. `fromEnvironment()` adlandırılmış
konstruktordur, yəni environment yalnız siz istəyəndə oxunur.

```php
$config = new PostaGuverciniConfig(username: 'my-user', password: 'secret');
// və ya
$config = PostaGuverciniConfig::fromEnvironment();
```

> [!WARNING]
> Bu servis **parolu hər sorğunun body-sində** göndərir (`Username`/`Password`
> field-ləri), header-də və ya imzada yox. Yəni sorğu body-ləri log-a düşməməlidir:
> LSIM-dən fərqli olaraq burada parol hash-lənmir, EPoint-dən fərqli olaraq
> imzalanmır — olduğu kimi gedir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `username` | `username` | `string` | ✅ |  | Hesabın istifadəçi adı. |
| `password` | `password` | `string` | ✅ |  | Hesabın parolu. **Hər sorğunun payload-una düşür.** |
| `originator` | `originator` | `string\|null` |  |  | Göndərən adı. `null` — hesabın default-u işləyir. |
