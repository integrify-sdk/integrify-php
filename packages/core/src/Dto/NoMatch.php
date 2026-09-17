<?php

declare(strict_types=1);

namespace Integrify\Dto;

/**
 * Tip çevirməsinin uğursuz olduğunu bildirən daxili sentinel.
 *
 * @internal
 */
enum NoMatch
{
    case Value;
}
