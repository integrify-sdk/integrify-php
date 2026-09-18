# Konfiqurasiya

`fromEnvironment()` adlandırılmış konstruktordur: dəyərlər bir dəfə, obyekt yaradılarkən oxunur — sorğu atılarkən yox.

## AzericardConfig

Azericard merchant hesabının məlumatları.

Dəyişməz value object-dir və klientə konstruktorda ötürülür — qlobal state və ya
sorğu anında environment oxuması yoxdur.

> [!IMPORTANT]
> **İki ayrı açar var.** MPI (kart ödənişləri) RSA private key istəyir — PKCS#1
> və ya PKCS#8 PEM. MT (pul köçürmələri) isə MD5 imzası üçün adi paylaşılan sətir
> açarı istəyir. Python kitabxanası ikisini də eyni `AZERICARD_KEY_FILE_PATH`
> dəyişənindən oxuyur, halbuki bir fayl hər ikisi ola bilməz — burada onlar
> ayrıdır və yalnız istifadə etdiyiniz sistem üçün açar vermək kifayətdir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `merchantId` | `merchantId` | `string` | ✅ |  | Bankın verdiyi terminal IDsi. |
| `privateKey` | `privateKey` | `OpenSSLAsymmetricKey\|null` |  |  | MPI sorğularını imzalayan RSA açarı. Yalnız kart əməliyyatları üçün lazımdır. |
| `transferKey` | `transferKey` | `string\|null` |  |  | MT sorğularının MD5 imzası üçün paylaşılan açar. Yalnız pul köçürmələri üçün lazımdır. |
| `merchantName` | `merchantName` | `string\|null` |  |  | Satıcının adı — kart sahibinin tanıyacağı formada. |
| `merchantUrl` | `merchantUrl` | `string\|null` |  |  | Satıcının saytı. |
| `merchantEmail` | `merchantEmail` | `string\|null` |  |  | Satıcının email ünvanı. |
| `callbackUrl` | `callbackUrl` | `string\|null` |  |  | Nəticənin post olunduğu URL. |
| `environment` | `environment` | `Environment` |  |  | Test və ya prod şlüzü. |
| `language` | `language` | `string` |  |  | Ödəniş səhifəsinin dili. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `privateKey()` | `OpenSSLAsymmetricKey` | MPI sorğuları üçün açar; yoxdursa aydın xəta. |
| `transferKey()` | `string` | MT sorğuları üçün açar; yoxdursa aydın xəta. |
| `baseUrl()` | `string` | Sistemə uyğun şlüz ünvanı. |
| `required()` | `string` | Kart əməliyyatlarında məcburi olan dəyər. |
