<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Testlər üçün PSR-18 klienti: hazır cavabları qaytarır və göndərilən sorğuları saxlayır.
 */
final class StubHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    private array $sent = [];

    /**
     * @param list<ResponseInterface> $responses
     */
    public function __construct(private array $responses = [])
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->sent[] = $request;

        return array_shift($this->responses) ?? new Response(200, [], '{}');
    }

    public function lastRequest(): RequestInterface
    {
        $last = end($this->sent);

        if ($last === false) {
            throw new RuntimeException('No request was sent.');
        }

        return $last;
    }
}
