<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

/** Test üçün string-backed enum. */
enum Colour: string
{
    case Red = 'red';
    case Blue = 'blue';
}
