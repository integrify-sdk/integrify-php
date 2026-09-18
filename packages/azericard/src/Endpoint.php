<?php

declare(strict_types=1);

namespace Integrify\Azericard;

/**
 * Azericard-ın endpoint-ləri.
 *
 * **İki ayrı sistem, iki ayrı host.** MPI (3-D Secure şlüzü) kart ödənişlərini,
 * MT isə pul köçürmələrini idarə edir; ünvanları da, imza alqoritmləri də fərqlidir:
 *
 * | Sistem | Host (test / prod) | İmza |
 * | :--- | :--- | :--- |
 * | MPI | `testmpi.3dsecure.az` / `mpi.3dsecure.az` | RSA SHA-256 (`P_SIGN`) |
 * | MT | `testmt.azericard.com` / `mt.azericard.com` | MD5 + paylaşılan açar |
 *
 * `System` enum-u hər endpoint-in hansı hosta getdiyini bildirir.
 */
enum Endpoint: string
{
    case Authorization = '/cgi-bin/cgi_link';
    case SaveCard = '/token/cgi_link';
    case Transfer = '/payment/view';
    case TransferConfirm = '/api/confirm';
    case TransferDecline = '/api/decline';

    /** Bu endpoint hansı sistemə aiddir. */
    public function system(): System
    {
        return match ($this) {
            self::Authorization, self::SaveCard => System::Mpi,
            self::Transfer, self::TransferConfirm, self::TransferDecline => System::Mt,
        };
    }
}
