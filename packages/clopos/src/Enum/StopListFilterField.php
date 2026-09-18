<?php

declare(strict_types=1);

namespace Integrify\Clopos\Enum;

/**
 * `getStopList()` filtrinin sahələri.
 *
 * Filtr **aralıq** verir: `from` məcburi, `to` opsionaldır.
 */
enum StopListFilterField: string
{
    /** Məhsulun IDsi. */
    case Id = 'id';

    /** Qalan say. */
    case Limit = 'limit';

    /** Son yenilənmənin unix vaxtı. */
    case Timestamp = 'timestamp';
}
