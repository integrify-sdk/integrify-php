# Xətalar

Hamısı `Integrify\Exception\IntegrifyException`-i implement edir.

## SignatureMismatch

Azericard-dan gələn datanın imzası uyğun gəlmir.

Callback URL-i və MT cavabları internetdən gəlir; imza yoxlaması "bu datanı
həqiqətən Azericard göndərdi" sualının yeganə cavabıdır.

Python kitabxanası bu yoxlamanı `assert` ilə edir — `python -O` altında `assert`
silinir, yəni optimizasiya ilə işləyən tətbiqdə yoxlama **ümumiyyətlə aparılmır**.
Burada yoxlama adi koddur.

Səbəblər, ehtimal sırası ilə: açar səhvdir (məs., test açarı ilə prod datası),
açarın faylda yazılışı (sonda boş sətir, CRLF) gözləniləndən fərqlidir, data
dəyişdirilib, və ya sorğu Azericard-dan gəlmir.

| Property | Məftildəki ad | Tip | Məcburi | Qaydalar | Təsvir |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `reason` | `reason` | `string` |  |  |  |
