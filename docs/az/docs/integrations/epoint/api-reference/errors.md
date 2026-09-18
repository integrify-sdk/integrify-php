# Xətalar

Hamısı `Integrify\Exception\IntegrifyException`-i implement edir.

## SignatureMismatch

Callback-in imzası gözlənilənlə uyğun gəlmir.

Callback URL-i internetdən əlçatandır, yəni ona istənilən kəs sorğu ata bilər.
İmza yoxlaması "bu datanı həqiqətən EPoint göndərdi" sualının **yeganə** cavabıdır,
ona görə uyğunsuzluq `null` qaytarmaqla deyil, exception ilə bildirilir: `null`
qaytarmaq onu yoxlamağı unutmağa imkan verirdi, və unudulduqda saxta "ödəniş
uğurludur" callback-i qəbul olunurdu.

Səbəblər, ehtimal sırası ilə: `EPOINT_PRIVATE_KEY` səhvdir (məs., test açarı ilə
prod callback-i), body sorğu keçdiyi yerdə dəyişdirilib, və ya sorğu EPoint-dən
gəlmir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `reason` | `reason` | `string` |  |  |  |
