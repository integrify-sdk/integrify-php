<?php

declare(strict_types=1);

namespace Integrify\Exception;

use Throwable;

/**
 * Integrify-in atdığı bütün exception-ların ortaq interfeysi.
 *
 * Kitabxanadan gələn istənilən xətanı bir yerdə tutmaq üçün:
 *
 * ```php
 * try {
 *     $client->declare($package);
 * } catch (IntegrifyException $exception) {
 *     // ...
 * }
 * ```
 */
interface IntegrifyException extends Throwable
{
}
