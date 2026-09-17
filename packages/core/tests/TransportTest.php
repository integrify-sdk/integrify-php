<?php

declare(strict_types=1);

namespace Integrify\Tests;

use Integrify\Exception\IntegrifyException;
use Integrify\Exception\RequestFailed;
use Integrify\Http\HttpTransport;
use Integrify\Http\RecordingTransport;
use Integrify\Http\Request;
use Integrify\Response;
use Integrify\Tests\Fixtures\OfflineHttpClient;
use Integrify\Tests\Fixtures\StubHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use stdClass;

/**
 * `HttpTransport` və `RecordingTransport` davranışı.
 */
final class TransportTest extends TestCase
{
    private StubHttpClient $http;

    /**
     * @param list<ResponseInterface> $responses
     */
    private function transport(array $responses = []): HttpTransport
    {
        $factory = new Psr17Factory();
        $this->http = new StubHttpClient($responses);

        return new HttpTransport($this->http, $factory, $factory);
    }

    public function testSendsBodyAsJson(): void
    {
        $transport = $this->transport([new PsrResponse(200, [], '{"ok":true}')]);

        $transport->send(new Request('POST', 'https://api.example.com/x', ['a' => 1]));

        $this->assertSame('POST', $this->http->lastRequest()->getMethod());
        $this->assertSame('{"a":1}', (string) $this->http->lastRequest()->getBody());
    }

    public function testEmptyObjectBodyIsSentAsBraces(): void
    {
        $transport = $this->transport([new PsrResponse(200, [], '{}')]);

        $transport->send(new Request('POST', 'https://api.example.com/x', new stdClass()));

        $this->assertSame('{}', (string) $this->http->lastRequest()->getBody());
    }

    public function testEmptyArrayBodyIsSentAsBrackets(): void
    {
        $transport = $this->transport([new PsrResponse(200, [], '{}')]);

        $transport->send(new Request('POST', 'https://api.example.com/x', []));

        // Boş toplu sorğu massiv olaraq qalmalıdır.
        $this->assertSame('[]', (string) $this->http->lastRequest()->getBody());
    }

    public function testUnencodableBodyThrowsInsteadOfSendingAnEmptyObject(): void
    {
        $transport = $this->transport([new PsrResponse(200, [], '{}')]);

        try {
            $transport->send(new Request('POST', 'https://api.example.com/x', ['bad' => "\xB1"]));
            $this->fail('Expected a RequestFailed exception.');
        } catch (RequestFailed $failure) {
            $this->assertStringContainsString('could not be encoded', $failure->getMessage());
        }
    }

    public function testRecordingTransportThrowsOnErrorStatus(): void
    {
        $transport = new RecordingTransport();
        $transport->queue(Response::json(['error' => true], 500));

        // `HttpTransport` 400+ üçün exception atır; test double eyni davranmalıdır.
        $this->expectException(RequestFailed::class);

        $transport->send(new Request('GET', 'https://x.test/'));
    }

    public function testNoBodyMeansNoPayload(): void
    {
        $transport = $this->transport([new PsrResponse(200, [], '{}')]);

        $transport->send(new Request('GET', 'https://api.example.com/x'));

        $this->assertSame('', (string) $this->http->lastRequest()->getBody());
    }

    public function testQueryStringIsAppended(): void
    {
        $transport = $this->transport([new PsrResponse(200, [], '{}')]);

        $transport->send(new Request('GET', 'https://api.example.com/x', query: ['a' => 1, 'b' => 'two']));

        $this->assertSame('a=1&b=two', $this->http->lastRequest()->getUri()->getQuery());
    }

    public function testHeadersArePassedThrough(): void
    {
        $transport = $this->transport([new PsrResponse(200, [], '{}')]);

        $transport->send(new Request('GET', 'https://api.example.com/x', headers: ['ApiKey' => 'secret']));

        $this->assertSame('secret', $this->http->lastRequest()->getHeaderLine('ApiKey'));
    }

    public function testHttpErrorBecomesRequestFailed(): void
    {
        $transport = $this->transport([new PsrResponse(503, [], 'gateway down')]);
        $request = new Request('GET', 'https://api.example.com/x');

        try {
            $transport->send($request);
            $this->fail('Expected a RequestFailed exception.');
        } catch (RequestFailed $failure) {
            $this->assertSame(503, $failure->response?->status);
            $this->assertSame('gateway down', $failure->response?->body);
            $this->assertSame($request, $failure->request);
            $this->assertStringContainsString('503', $failure->getMessage());
        }
    }

    public function testTransportErrorBecomesRequestFailed(): void
    {
        $factory = new Psr17Factory();
        $transport = new HttpTransport(new OfflineHttpClient(), $factory, $factory);

        try {
            $transport->send(new Request('GET', 'https://api.example.com/x'));
            $this->fail('Expected a RequestFailed exception.');
        } catch (RequestFailed $failure) {
            $this->assertNull($failure->response);
            $this->assertStringContainsString('offline', $failure->getMessage());
        }
    }

    public function testRequestFailedIsAnIntegrifyException(): void
    {
        $this->expectException(IntegrifyException::class);

        $this->transport([new PsrResponse(500, [], '')])->send(new Request('GET', 'https://x.test/'));
    }

    public function testRecordingTransportCanReplayQueuedFailures(): void
    {
        $transport = new RecordingTransport();
        $request = new Request('GET', 'https://x.test/');
        $transport->queue(RequestFailed::httpError($request, new Response(500, [], '')));

        $this->expectException(RequestFailed::class);

        $transport->send($request);
    }

    public function testRecordingTransportFallsBackWhenQueueIsEmpty(): void
    {
        $transport = new RecordingTransport(Response::json(['fallback' => true]));

        $response = $transport->send(new Request('GET', 'https://x.test/'));

        $this->assertSame(['fallback' => true], $response->toArray());
    }

    public function testRecordingTransportResetClearsState(): void
    {
        $transport = new RecordingTransport(Response::json([]));
        $transport->send(new Request('GET', 'https://x.test/'));

        $this->assertSame(1, $transport->count());

        $transport->reset();

        $this->assertSame(0, $transport->count());
    }

    public function testRecordingTransportRefusesAnUnqueuedRequest(): void
    {
        // Növbə boşdursa səssizcə `200` qaytarmaq "bir dəfə çağırılır" testinin
        // dörd çağırışda da keçməsinə səbəb olurdu.
        $this->expectException(RuntimeException::class);

        (new RecordingTransport())->send(new Request('GET', 'https://x.test/'));
    }

    public function testRecordingTransportAcceptsAnExplicitFallback(): void
    {
        $transport = new RecordingTransport(Response::json(['stub' => true]));

        $first = $transport->send(new Request('GET', 'https://x.test/'));
        $second = $transport->send(new Request('GET', 'https://x.test/'));

        $this->assertSame(200, $first->status);
        $this->assertSame(200, $second->status);
    }

    public function testRecordingTransportRejectsAnUnencodableBodyLikeTheRealOne(): void
    {
        // `HttpTransport` bunu atır; test double da atmalıdır, əks halda `INF`
        // yalnız production-da üzə çıxır.
        $this->expectException(RequestFailed::class);

        (new RecordingTransport(Response::json([])))
            ->send(new Request('POST', 'https://x.test/', ['amount' => INF]));
    }

    public function testLastRequestThrowsWhenNothingWasSent(): void
    {
        $this->expectException(RuntimeException::class);

        (new RecordingTransport())->lastRequest();
    }

    public function testRequestIsImmutable(): void
    {
        $request = new Request('GET', 'https://x.test/');
        $withHeader = $request->withHeader('A', 'b');

        $this->assertSame([], $request->headers);
        $this->assertSame(['A' => 'b'], $withHeader->headers);
        $this->assertSame(['A' => 'b', 'C' => 'd'], $withHeader->withHeaders(['C' => 'd'])->headers);
    }
}
