---
title: Clopos · Konfiqurasiya
---

# Konfiqurasiya

`fromEnvironment()` adlandırılmış konstruktordur: dəyərlər bir dəfə, obyekt yaradılarkən oxunur — sorğu atılarkən yox.

## CloposConfig

Clopos klientinin konfiqurasiyası.

Dörd dəyər token almaq üçündür (`clientId`, `clientSecret`, `brand`,
`integratorId`); `venueId` isə opsionaldır və **hər sorğuda** `x-venue` header-i
kimi gedir.

> Token-in özü burada saxlanılmır. O, `AzericardConfig`-dəki açar kimi sabit deyil —
> bir saat yaşayır və `authenticate()` ilə alınır, ona görə klientin dəyişən
> vəziyyətidir, konfiqurasiya deyil.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `clientId` | `clientId` | `string\|null` |  |  | Clopos-un verdiyi client ID. Yalnız `authenticate()` üçün. |
| `clientSecret` | `clientSecret` | `string\|null` |  |  | Clopos-un verdiyi client secret. Yalnız `authenticate()` üçün. |
| `brand` | `brand` | `string\|null` |  |  | Brend adı — token məhz bu brendə verilir. |
| `integratorId` | `integratorId` | `string\|null` |  |  | Clopos-un verdiyi inteqrator IDsi. |
| `venueId` | `venueId` | `string\|null` |  |  | Filialın IDsi. Verilsə, hər sorğuya `x-venue` header-i əlavə olunur və JWT-nin içindəki filialı **həmin sorğu üçün** əvəz edir. |
| `baseUrl` | `baseUrl` | `string` |  |  | API-nin baza url-i. Test mühiti üçün dəyişdirilə bilər. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `credentials()` | `array` | `authenticate()` üçün lazım olan dörd dəyər. |
