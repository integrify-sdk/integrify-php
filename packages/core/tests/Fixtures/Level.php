<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

/** Test üçün int-backed enum. */
enum Level: int
{
    case Low = 1;
    case High = 2;
}
