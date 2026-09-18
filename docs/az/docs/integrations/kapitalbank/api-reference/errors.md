# Xətalar

Hamısı `Integrify\Exception\IntegrifyException`-i implement edir.

## RequestRejected

Bank sorğunu rədd etdi.

Kapital Bank xətanı HTTP status kodu ilə bildirir (EPoint-dən fərqli olaraq, orada
hər cavab 200-dür), və body-də struktur verir:

```json
{"errorCode": "InvalidOrderState", "errorDescription": "...", "errorDetails": {...}}
```

`Transport` belə cavab üçün `RequestFailed` atır, lakin onun mesajı yalnız status
kodunu göstərir. Bu class həmin body-ni oxuyub **səbəbi** görünən yerə çıxarır:

```php
try {
    $client->refundOrder($orderId, 10);
} catch (RequestRejected $rejection) {
    $rejection->errorCode;        // 'InvalidOrderState'
    $rejection->code();           // ErrorCode::InvalidOrderState
    $rejection->description;      // bankın izahı
    $rejection->getPrevious();    // orijinal RequestFailed (cavab burada)
}
```

Python kitabxanası əvəzinə `APIResponse.ok = false` qaytarır və xətanı
`body.error`-da saxlayır — yəni yoxlamağı unutmaq mümkündür. Burada unutmaq
mümkün deyil.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `errorCode` | `errorCode` | `string\|null` | ✅ |  | Bankın xam `errorCode` dəyəri. |
| `description` | `description` | `string\|null` | ✅ |  | Bankın `errorDescription` izahı. |
| `details` | `details` | `?array` | ✅ |  | Bankın `errorDetails` strukturu. Bankın sənədləri bu strukturun formasına zəmanət vermir, ona görə tip dar deyil — açarların sətir olduğunu güman etmək olmaz. |
| `failure` | `failure` | `RequestFailed` | ✅ |  | Orijinal xəta — cavab və sorğu onun üzərindədir. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `code()` | `?ErrorCode` | `errorCode`-un enum qarşılığı, tanınırsa. |
