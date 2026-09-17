<?php

declare(strict_types=1);

namespace Integrify\EPoint\Exception;

use Integrify\Exception\IntegrifyException;
use RuntimeException;

/**
 * Callback-in imzası gözlənilənlə uyğun gəlmir.
 *
 * Callback URL-i internetdən əlçatandır, yəni ona istənilən kəs sorğu ata bilər.
 * İmza yoxlaması "bu datanı həqiqətən EPoint göndərdi" sualının **yeganə** cavabıdır,
 * ona görə uyğunsuzluq `null` qaytarmaqla deyil, exception ilə bildirilir: `null`
 * qaytarmaq onu yoxlamağı unutmağa imkan verirdi, və unudulduqda saxta "ödəniş
 * uğurludur" callback-i qəbul olunurdu.
 *
 * Səbəblər, ehtimal sırası ilə: `EPOINT_PRIVATE_KEY` səhvdir (məs., test açarı ilə
 * prod callback-i), body sorğu keçdiyi yerdə dəyişdirilib, və ya sorğu EPoint-dən
 * gəlmir.
 */
final class SignatureMismatch extends RuntimeException implements IntegrifyException
{
    public function __construct(string $reason = 'the signature does not match the payload')
    {
        parent::__construct(sprintf(
            'Refusing to trust this EPoint callback: %s. '
            . 'Check that EPOINT_PRIVATE_KEY matches the account the callback came from.',
            $reason,
        ));
    }
}
