<?php

declare(strict_types=1);

namespace Integrify\Azericard\Enum;

/**
 * Köçürmə callback-ində gələn kart vəziyyəti.
 */
enum CardStatus: string
{
    /** Kart Azericard bazasındadır və aktivdir. */
    case Active = 'our_active';

    /** Kart Azericard bazasındadır, lakin aktiv deyil (bloklanmış, müddəti bitmiş). */
    case Inactive = 'our_inactive';

    /** Kart Azericard bazasında yoxdur. */
    case Missing = 'foreign';
}
