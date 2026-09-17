<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * PSR-18 klientinin atdığı şəbəkə xətasını təqlid edir.
 */
final class OfflineException extends RuntimeException implements ClientExceptionInterface
{
}
