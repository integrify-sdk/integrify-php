<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Həmişə şəbəkə xətası atan PSR-18 klienti.
 */
final class OfflineHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        throw new OfflineException('offline');
    }
}
