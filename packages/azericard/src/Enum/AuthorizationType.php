<?php

declare(strict_types=1);

namespace Integrify\Azericard\Enum;

/**
 * Kart əməliyyatının növü (`TRTYPE`).
 */
enum AuthorizationType: string
{
    /** Məbləği kartda bloklayır, silmir. Sonra `finalize()` ilə tamamlanır. */
    case Freeze = '0';

    /** Məbləği birbaşa silir. */
    case Direct = '1';
}
