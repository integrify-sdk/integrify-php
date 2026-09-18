---
title: Clopos · Xətalar
---

# Xətalar

Hamısı `Integrify\Exception\IntegrifyException`-i implement edir.

## RequestRejected

Clopos sorğunu rədd etdi.

Clopos xətanı HTTP status kodu ilə bildirir və body-də struktur verir:

```json
{
  "success": false,
  "error": [
    {
      "message": "No query results for model [App\\Models\\Auth\\User] 1000",
      "type": "server_side",
      "exception": "NotFoundHttpException",
      "http_code": 404
    }
  ]
}
```

`Transport` belə cavab üçün `RequestFailed` atır, lakin onun mesajı yalnız status
kodunu göstərir. Bu class həmin body-ni oxuyub **səbəbi** görünən yerə çıxarır:

```php
try {
    $client->getUser(1000);
} catch (RequestRejected $rejection) {
    $rejection->errors[0]->exception;   // 'NotFoundHttpException'
    $rejection->errors[0]->httpCode;    // 404
    $rejection->getPrevious();          // orijinal RequestFailed (cavab burada)
}
```

Python kitabxanası əvəzinə `APIResponse.ok = false` qaytarır və xətanı
`body.error`-da saxlayır — yəni yoxlamağı unutmaq mümkündür. Burada unutmaq
mümkün deyil.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `errors` | `errors` | `list<ErrorDetail>` | ✅ |  | Servisin verdiyi xəta siyahısı. Body gözlənilən formada olmasa boş qalır — status kodu özü artıq "uğursuz" deməkdir. |
| `summary` | `summary` | `string\|null` | ✅ |  | Zərfin öz `message` field-i. |
| `failure` | `failure` | `RequestFailed` | ✅ |  | Orijinal xəta — sorğu və cavab onun üzərindədir. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `messages()` | `array` | Bütün xəta mesajları, sıra ilə. |
