<?php

declare(strict_types=1);

namespace Integrify\Exception;

use Integrify\Http\Request;
use Integrify\Response;
use RuntimeException;
use Throwable;

/**
 * Sorğu uğursuz olduqda atılır: şəbəkə xətası, və ya 400-dən böyük status kodu.
 *
 * Metodların qaytarış tipləri konkret olduğu üçün (`list<GoodsGroup>`, `Declaration`
 * və s.) uğursuzluğu qaytarış dəyəri ilə bildirmək mümkün deyil — ona görə HTTP
 * səviyyəsindəki xətalar exception kimi qalxır. Cavab obyekti exception-un
 * üzərində saxlanılır:
 *
 * ```php
 * try {
 *     $groups = $client->listGoodsGroups();
 * } catch (RequestFailed $exception) {
 *     $exception->response?->status;
 *     $exception->response?->body;
 * }
 * ```
 *
 * Servisin özünün bildirdiyi məntiqi xətalar (HTTP 200, lakin `code !== 200`) bura
 * daxil deyil — onlar `OperationResult::isSuccessful()` ilə yoxlanılır.
 */
final class RequestFailed extends RuntimeException implements IntegrifyException
{
    public function __construct(
        public readonly Request $request,
        public readonly ?Response $response = null,
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $message !== '' ? $message : self::describe($request, $response),
            $response === null ? 0 : $response->status,
            $previous,
        );
    }

    /**
     * Şəbəkə səviyyəsində baş verən xəta (cavab ümumiyyətlə alınmayıb).
     */
    public static function transportError(Request $request, Throwable $previous): self
    {
        return new self(
            request: $request,
            message: sprintf(
                '%s %s failed: %s',
                $request->method,
                $request->uri,
                $previous->getMessage(),
            ),
            previous: $previous,
        );
    }

    /**
     * 400-dən böyük status kodu ilə gələn cavab.
     */
    public static function httpError(Request $request, Response $response): self
    {
        return new self($request, $response);
    }

    private static function describe(Request $request, ?Response $response): string
    {
        if ($response === null) {
            return sprintf('%s %s failed.', $request->method, $request->uri);
        }

        return sprintf(
            '%s %s returned HTTP %d. Body: %s',
            $request->method,
            $request->uri,
            $response->status,
            self::truncate($response->body),
        );
    }

    private static function truncate(string $body, int $limit = 500): string
    {
        $body = trim($body);

        if ($body === '') {
            return '(empty)';
        }

        return mb_strlen($body) > $limit ? mb_substr($body, 0, $limit) . '…' : $body;
    }
}
