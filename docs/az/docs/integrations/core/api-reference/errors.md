---
title: Core · Xətalar
---

# Xətalar

Hamısı `Integrify\Exception\IntegrifyException`-i implement edir.

## IntegrifyException

Integrify-in atdığı bütün exception-ların ortaq interfeysi.

Kitabxanadan gələn istənilən xətanı bir yerdə tutmaq üçün:

```php
try {
    $client->declare($package);
} catch (IntegrifyException $exception) {
    // ...
}
```

_Field-i yoxdur._

## InvalidRequest

Sorğu göndərilməmişdən əvvəl, klient kodundakı səhv aşkar edildikdə atılır.

Məsələn: path-də kodlanmamış `?`/`#`, doldurulmamış `{placeholder}`, və ya
`baseUrl`-dən fərqli host-a yönəlmiş mütləq url.

`RequestFailed`-dən fərqlidir: orada sorğu **göndərilib** və uğursuz olub,
burada isə sorğu heç yola düşməyib.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `message` | `message` | `string` |  |  |  |
| `code` | `code` | `int` |  |  |  |
| `previous` | `previous` | `?Throwable` |  |  |  |

## MissingConfiguration

Konfiqurasiya environment-dən oxunarkən məcburi dəyər tapılmadıqda atılır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `variable` | `variable` | `string` | ✅ |  |  |
| `hint` | `hint` | `string` |  |  |  |

## RequestFailed

Sorğu uğursuz olduqda atılır: şəbəkə xətası, və ya 400-dən böyük status kodu.

Metodların qaytarış tipləri konkret olduğu üçün (`list<GoodsGroup>`, `Declaration`
və s.) uğursuzluğu qaytarış dəyəri ilə bildirmək mümkün deyil — ona görə HTTP
səviyyəsindəki xətalar exception kimi qalxır. Cavab obyekti exception-un
üzərində saxlanılır:

```php
try {
    $groups = $client->listGoodsGroups();
} catch (RequestFailed $exception) {
    $exception->response?->status;
    $exception->response?->body;
}
```

Servisin özünün bildirdiyi məntiqi xətalar (HTTP 200, lakin `code !== 200`) bura
daxil deyil — onlar `OperationResult::isSuccessful()` ilə yoxlanılır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `request` | `request` | `Request` | ✅ |  |  |
| `response` | `response` | `?Response` |  |  |  |
| `message` | `message` | `string` |  |  |  |
| `previous` | `previous` | `?Throwable` |  |  |  |

## ValidationFailed

DTO validasiyası uğursuz olduqda atılır.

Bütün xətalar bir yerə toplanır — ilk xətada dayanılmır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `dto` | `dto` | `class-string` | ✅ |  | Validasiya olunan DTO-nun adı. |
| `errors` | `errors` | `array` | ✅ |  | `field => mesaj` şəklində xətalar. |
