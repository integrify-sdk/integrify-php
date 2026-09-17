<?php

declare(strict_types=1);

namespace Integrify\Http;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Integrify\Exception\RequestFailed;
use Integrify\Response;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * PSR-18 üzərində qurulmuş transport.
 *
 * Klient və factory-lər verilməzsə, `php-http/discovery` vasitəsilə tətbiqdə
 * mövcud olan implementasiyalar tapılır (Guzzle, Symfony HttpClient və s.).
 * Hər şey lazy qurulur: obyekt yaradılarkən yox, ilk sorğuda.
 */
final class HttpTransport implements Transport
{
    private ?ClientInterface $client;

    private ?RequestFactoryInterface $requests;

    private ?StreamFactoryInterface $streams;

    private LoggerInterface $logger;

    public function __construct(
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requests = null,
        ?StreamFactoryInterface $streams = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->client = $client;
        $this->requests = $requests;
        $this->streams = $streams;
        $this->logger = $logger ?? new NullLogger();
    }

    public function send(Request $request): Response
    {
        try {
            $psr = $this->toPsrRequest($request);
        } catch (RequestFailed $exception) {
            // `encode()` artıq dolğun `RequestFailed` qurub — onu yenidən bürümək
            // mesajı ikiləşdirir və kodlama xətasını şəbəkə xətası kimi göstərirdi.
            throw $exception;
        } catch (\Throwable $exception) {
            // PSR-7 factory-si və ya discovery öz exception-larını atır; onlar
            // `IntegrifyException` deyil, ona görə burada bürünür.
            throw RequestFailed::transportError($request, $exception);
        }

        try {
            // PSR-18 yalnız `ClientExceptionInterface` vəd edir, lakin middleware-lər
            // və qeyri-dəqiq implementasiyalar `LogicException`, `TypeError` və s. ata
            // bilir. `IntegrifyException` "kitabxanadan gələn hər şey" deməkdirsə,
            // burada hamısı tutulmalıdır.
            $psrResponse = $this->client()->sendRequest($psr);
        } catch (\Throwable $exception) {
            $this->logger->error('{method} {uri} failed: {reason}', [
                'method' => $request->method,
                'uri' => $request->uri,
                'reason' => $exception->getMessage(),
            ]);

            throw RequestFailed::transportError($request, $exception);
        }

        /** @var array<string, array<string>> $headers */
        $headers = $psrResponse->getHeaders();

        $response = new Response(
            status: $psrResponse->getStatusCode(),
            headers: $headers,
            body: (string) $psrResponse->getBody(),
        );

        if (!$response->isSuccessful()) {
            $this->logger->error('{method} {uri} returned HTTP {status}: {body}', [
                'method' => $request->method,
                'uri' => $request->uri,
                'status' => $response->status,
                'body' => $response->body,
            ]);

            throw RequestFailed::httpError($request, $response);
        }

        return $response;
    }

    private function toPsrRequest(Request $request): \Psr\Http\Message\RequestInterface
    {
        $uri = self::withQuery($request->uri, $request->query);

        $psr = $this->requests()->createRequest($request->method, $uri);

        foreach ($request->headers as $name => $value) {
            $psr = $psr->withHeader($name, $value);
        }

        if ($request->body !== null) {
            $psr = $psr->withBody($this->streams()->createStream(self::encode($request)));
        }

        return $psr;
    }

    /**
     * Query parametrlərini url-ə əlavə edir.
     *
     * Fragment (`#...`) RFC 3986-ya görə url-in **sonunda** olmalıdır. Əvvəllər query
     * sadəcə sona yapışdırılırdı və `https://a.com/x#frag` + `?a=1` nəticəsində
     * `https://a.com/x#frag?a=1` alınırdı — `a=1` fragment-in içinə düşür və
     * ümumiyyətlə məftilə çıxmırdı.
     *
     * @param array<string, scalar> $query
     */
    private static function withQuery(string $uri, array $query): string
    {
        if ($query === []) {
            return $uri;
        }

        $fragment = '';
        $hash = strpos($uri, '#');

        if ($hash !== false) {
            $fragment = substr($uri, $hash);
            $uri = substr($uri, 0, $hash);
        }

        $encoded = http_build_query($query);

        if (str_contains($uri, '?')) {
            // `https://a.com/x?` -> `?a=1`, `https://a.com/x?b=2` -> `?b=2&a=1`
            $uri .= str_ends_with($uri, '?') ? $encoded : '&' . $encoded;
        } else {
            $uri .= '?' . $encoded;
        }

        return $uri . $fragment;
    }

    /**
     * @throws RequestFailed Data JSON-a çevrilə bilmirsə (məs., yararsız UTF-8, `INF`).
     */
    private static function encode(Request $request): string
    {
        try {
            return json_encode(
                $request->body,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            // Əvvəllər burada səssizcə `{}` qaytarılırdı — bütün payload itirdi.
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

    private function client(): ClientInterface
    {
        return $this->client ??= Psr18ClientDiscovery::find();
    }

    private function requests(): RequestFactoryInterface
    {
        return $this->requests ??= Psr17FactoryDiscovery::findRequestFactory();
    }

    private function streams(): StreamFactoryInterface
    {
        return $this->streams ??= Psr17FactoryDiscovery::findStreamFactory();
    }
}
