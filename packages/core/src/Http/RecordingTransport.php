<?php

declare(strict_types=1);

namespace Integrify\Http;

use Integrify\Exception\RequestFailed;
use Integrify\Response;
use JsonException;
use RuntimeException;

/**
 * Sorğu göndərmədən onları yadda saxlayan transport.
 *
 * İki işə yarayır:
 *
 * 1. **Testlər** — hazır cavabları növbəyə qoyub göndərilən sorğuları yoxlamaq;
 * 2. **Debug** — real sorğu atmadan hansı url-in, header-lərin və payload-un
 *    göndəriləcəyini görmək (əvvəlki "dry" rejiminin əvəzi, lakin klientin
 *    qaytarış tiplərini pozmadan).
 *
 * ```php
 * $transport = new RecordingTransport();
 * $transport->queue(Response::json(['code' => 200]));
 *
 * $client = new EPointClient($config, $transport);
 * $client->declare($package);
 *
 * $transport->lastRequest()->uri;   // göndərilən url
 * $transport->lastRequest()->body;  // göndərilən payload
 * ```
 */
final class RecordingTransport implements Transport
{
    /** @var list<Request> */
    private array $requests = [];

    /** @var list<Response|RequestFailed> */
    private array $queued = [];

    private ?Response $fallback;

    /**
     * @param Response|null $fallback Növbə boşaldıqdan sonra qaytarılacaq cavab.
     *     Verilməsə, gözlənilməyən sorğu **exception** qaldırır: səssizcə `200`
     *     qaytarmaq "bir dəfə çağırılır" testinin dörd çağırışda da keçməsinə
     *     səbəb olurdu.
     */
    public function __construct(?Response $fallback = null)
    {
        $this->fallback = $fallback;
    }

    /**
     * Növbəti sorğunun cavabını növbəyə qoyur.
     */
    public function queue(Response|RequestFailed ...$responses): self
    {
        foreach ($responses as $response) {
            $this->queued[] = $response;
        }

        return $this;
    }

    public function send(Request $request): Response
    {
        $this->requests[] = $request;

        // `HttpTransport` payload-u JSON-a çevirə bilmirsə atır; test double da
        // eyni etməlidir, əks halda `INF`/`NAN`/yararsız UTF-8 yalnız production-da
        // üzə çıxır.
        if ($request->body !== null) {
            try {
                json_encode(
                    $request->body,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                );
            } catch (JsonException $exception) {
                throw new RequestFailed(
                    request: $request,
                    message: sprintf(
                        '%s %s: request body could not be encoded as JSON: %s',
                        $request->method,
                        $request->uri,
                        $exception->getMessage(),
                    ),
                    previous: $exception,
                );
            }
        }

        $next = array_shift($this->queued) ?? $this->fallback;

        if ($next === null) {
            throw new RuntimeException(sprintf(
                'RecordingTransport has no queued response for %s %s (request #%d). '
                . 'Queue one with queue(), or pass a fallback to the constructor.',
                $request->method,
                $request->uri,
                count($this->requests),
            ));
        }

        if ($next instanceof RequestFailed) {
            throw $next;
        }

        // `Transport` müqaviləsi: 400+ status kodu exception kimi qalxır.
        // `HttpTransport` belə edir, ona görə test double da eyni davranmalıdır —
        // əks halda testlər real həyatda mümkün olmayan axını yoxlayardı.
        if (!$next->isSuccessful()) {
            throw RequestFailed::httpError($request, $next);
        }

        return $next;
    }

    /**
     * Göndərilmiş bütün sorğular.
     *
     * @return list<Request>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    /**
     * Son göndərilmiş sorğu.
     */
    public function lastRequest(): Request
    {
        $last = end($this->requests);

        if ($last === false) {
            throw new RuntimeException('No request has been sent yet.');
        }

        return $last;
    }

    public function count(): int
    {
        return count($this->requests);
    }

    /**
     * Yadda saxlanmış sorğuları və növbəni təmizləyir.
     */
    public function reset(): self
    {
        $this->requests = [];
        $this->queued = [];

        return $this;
    }
}
