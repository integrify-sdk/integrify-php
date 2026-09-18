# Digər obyektlər

## Callback

Azericard-ın callback URL-inizə post etdiyi datanın oxunması.

İki callback var və **təhlükəsizlik baxımından eyni deyillər**:

| Callback | İmza | Yoxlanıla bilirmi |
| :--- | :--- | :--- |
| Kart əməliyyatı (`decodeAuth()`) | `P_SIGN`, RSA | **xeyr** — Azericard-ın public açarı lazımdır |
| Pul köçürməsi (`decodeTransfer()`) | `Signature`, MD5 | **bəli** — paylaşılan açar |

```php
$callback = new Callback(AzericardConfig::fromEnvironment());

$data = $callback->decodeAuth($_POST);

if ($data->isSuccessful()) {
    // $data->order ilə sifarişi tapın — sonra getTransactionStatus() ilə təsdiqləyin
}
```

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `config` | `config` | `AzericardConfig` | ✅ |  |  |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `decodeAuth()` | `AuthCallback` | Kart əməliyyatının callback-ini oxuyur. |
| `decodeTransfer()` | `TransferCallback` | Pul köçürməsinin callback-ini oxuyur və **imzasını yoxlayır**. |
| `verifyTransferResult()` | `TransferResult` | `confirmTransfer()` / `declineTransfer()` cavabının imzasını yoxlayır. |

## FormPost

Brauzerin göndərməli olduğu forma.

**Azericard-ın kart əməliyyatları server-server sorğu deyil.** `authorize()`,
`authorizeAndSaveCard()`, `authorizeWithSavedCard()`, `finalize()` və
`startTransfer()` heç bir HTTP sorğusu atmır — onlar müştərinin brauzerinin
Azericard-a **özünün** göndərməli olduğu formanı qaytarır. Ona görə bu metodların
cavabı yoxdur: nəticə sonradan callback URL-inizə gəlir.

```php
$form = $client->authorize(amount: 10.5, currency: '944', order: '123456', description: 'Sifariş');

echo $form->toHtml();   // özünü göndərən forma
```

Öz şablonunuzu qurmaq istəyirsinizsə `$url`, `$method` və `$fields` açıqdır.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `url` | `url` | `string` | ✅ |  | Formanın göndəriləcəyi ünvan. |
| `method` | `method` | `string` | ✅ |  | HTTP metodu (`POST` və ya `GET`). |
| `fields` | `fields` | `array` | ✅ |  | Gizli field-lər — imza da daxil. |

| Metod | Qaytarır | Nə edir |
| :--- | :--- | :--- |
| `toHtml()` | `string` | Özünü göndərən HTML forması. |
| `toArray()` | `array` | Formanı massiv kimi qaytarır — öz şablonunuz və ya testlər üçün. |

## Signature

Azericard-ın **iki fərqli** imza alqoritmi.

MPI (kart ödənişləri) RSA SHA-256 istifadə edir və nəticə `P_SIGN` field-inə düşür.
MT (pul köçürmələri) isə MD5 + paylaşılan açar istifadə edir və nəticə `Signature`
field-inə düşür. İkisinin açarı da fərqlidir — bax: [`AzericardConfig`](config.md#azericardconfig).

_Field-i yoxdur._
