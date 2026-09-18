<?php

declare(strict_types=1);

namespace Integrify\Azericard;

use Integrify\Environment;

/**
 * Azericard-ın iki alt sistemi.
 *
 * Ayrı host, ayrı açar, ayrı imza alqoritmi — ona görə ayrı enum.
 */
enum System: string
{
    /** 3-D Secure şlüzü: kart ödənişləri. RSA SHA-256 ilə imzalanır. */
    case Mpi = 'mpi';

    /** Money Transfer: pul köçürmələri. MD5 + paylaşılan açar ilə imzalanır. */
    case Mt = 'mt';

    public function baseUrl(Environment $environment): string
    {
        return match ($this) {
            self::Mpi => $environment->isProduction()
                ? 'https://mpi.3dsecure.az'
                : 'https://testmpi.3dsecure.az',
            self::Mt => $environment->isProduction()
                ? 'https://mt.azericard.com'
                : 'https://testmt.azericard.com',
        };
    }
}
