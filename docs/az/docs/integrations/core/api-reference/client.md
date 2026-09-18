---
title: Core · Klient
---

# Client

İnteqrasiya klientlərinin baza class-ı.

Bu kitabxanada sorğular **adi, tipli metodlardır** — magic dispatch yoxdur.
Baza class yalnız ortaq mexanikanı verir: baza url, hər sorğuya əlavə olunan
header-lər və `Transport`-a ötürmə.

```php
final class MyClient extends Client
{
    public function pay(int $amount, string $orderId): PaymentResult
    {
        return $this->post('/pay', ['amount' => $amount, 'order_id' => $orderId])
            ->to(PaymentResult::class);
    }
}
```
