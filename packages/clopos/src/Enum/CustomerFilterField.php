<?php

declare(strict_types=1);

namespace Integrify\Clopos\Enum;

/**
 * `listCustomers()` filtrinin sahələri.
 */
enum CustomerFilterField: string
{
    case Name = 'name';
    case Phones = 'phones';
    case GroupId = 'group_id';
}
