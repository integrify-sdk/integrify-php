<?php

declare(strict_types=1);

namespace Integrify\Azericard\Exception;

use Integrify\Exception\IntegrifyException;
use RuntimeException;

/**
 * Azericard-dan gələn datanın imzası uyğun gəlmir.
 *
 * Callback URL-i və MT cavabları internetdən gəlir; imza yoxlaması "bu datanı
 * həqiqətən Azericard göndərdi" sualının yeganə cavabıdır.
 *
 * Python kitabxanası bu yoxlamanı `assert` ilə edir — `python -O` altında `assert`
 * silinir, yəni optimizasiya ilə işləyən tətbiqdə yoxlama **ümumiyyətlə aparılmır**.
 * Burada yoxlama adi koddur.
 *
 * Səbəblər, ehtimal sırası ilə: açar səhvdir (məs., test açarı ilə prod datası),
 * açarın faylda yazılışı (sonda boş sətir, CRLF) gözləniləndən fərqlidir, data
 * dəyişdirilib, və ya sorğu Azericard-dan gəlmir.
 */
final class SignatureMismatch extends RuntimeException implements IntegrifyException
{
    public function __construct(string $reason = 'the signature does not match the payload')
    {
        parent::__construct(sprintf(
            'Refusing to trust this Azericard payload: %s. '
            . 'Check that the transfer key matches the account, and that its file has no trailing '
            . 'newline or CRLF line endings the service does not expect.',
            $reason,
        ));
    }
}
