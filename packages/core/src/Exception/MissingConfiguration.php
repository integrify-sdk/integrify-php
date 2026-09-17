<?php

declare(strict_types=1);

namespace Integrify\Exception;

use RuntimeException;

/**
 * Konfiqurasiya environment-dən oxunarkən məcburi dəyər tapılmadıqda atılır.
 */
final class MissingConfiguration extends RuntimeException implements IntegrifyException
{
    public function __construct(public readonly string $variable, string $hint = '')
    {
        parent::__construct(sprintf(
            'Environment variable %s is not set.%s',
            $variable,
            $hint === '' ? '' : ' ' . $hint,
        ));
    }
}
