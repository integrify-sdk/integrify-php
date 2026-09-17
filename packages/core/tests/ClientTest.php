<?php

declare(strict_types=1);

namespace Integrify\Tests;

use Integrify\Exception\InvalidRequest;
use Integrify\Http\RecordingTransport;
use Integrify\Response;
use Integrify\Tests\Fixtures\Blank;
use Integrify\Tests\Fixtures\Child;
use Integrify\Tests\Fixtures\Sample;
use Integrify\Tests\Fixtures\SampleClient;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * `Client` baza class-ının davranışı.
 */
final class ClientTest extends TestCase
{
    private RecordingTransport $transport;

    private SampleClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = new SampleClient($this->transport);
    }

    public function testBuildsUriFromBaseUrlAndPath(): void
    {
        $this->transport->queue(Response::json(['namE_OF_THING' => 'abc']));

        $this->client->fetch('res-1');

        $this->assertSame('https://api.example.com/things/res-1', $this->transport->lastRequest()->uri);
        $this->assertSame('GET', $this->transport->lastRequest()->method);
    }

    public function testPathSegmentsAreEncodedByTheCaller(): void
    {
        $this->transport->queue(Response::json(['namE_OF_THING' => 'abc']));

        $this->client->fetch('a b/c');

        $this->assertSame('https://api.example.com/things/a%20b%2Fc', $this->transport->lastRequest()->uri);
    }

    public function testAbsoluteUrlOnTheSameHostIsAllowed(): void
    {
        $this->transport->queue(Response::json(['total' => 1]));

        $this->client->absolute();

        $this->assertSame('https://api.example.com/ping', $this->transport->lastRequest()->uri);
    }

    public function testAbsoluteUrlOnAForeignHostIsRefused(): void
    {
        // `defaultHeaders()` API açarını daşıyır — yad host-a sorğu onu sızdırardı.
        $this->expectException(InvalidRequest::class);

        $this->client->offsite();
    }

    public function testPathMayNotSmuggleQueryParameters(): void
    {
        $this->expectException(InvalidRequest::class);

        $this->client->fetchRaw('42?admin=1');
    }

    public function testUriTemplateEncodesItsParameters(): void
    {
        $this->transport->queue(Response::json(['namE_OF_THING' => 'abc']));

        $this->client->fetchTemplated('42?admin=1');

        $this->assertSame(
            'https://api.example.com/things/42%3Fadmin%3D1',
            $this->transport->lastRequest()->uri,
        );
    }

    public function testDefaultHeadersAreApplied(): void
    {
        $this->transport->queue(Response::json(['total' => 1]));

        $this->client->traced();

        $headers = $this->transport->lastRequest()->headers;

        // GET-in body-si yoxdur, ona görə `Content-Type` göndərilmir.
        $this->assertFalse(isset($headers['Content-Type']));
        $this->assertSame('sample', $headers['X-Client']);
        $this->assertSame('abc', $headers['X-Trace']);
    }

    public function testContentTypeIsSentOnlyWithABody(): void
    {
        $this->transport->queue(Response::json(['total' => 1]));

        $this->client->create(Sample::from(['name' => 'abc']));

        $this->assertSame('application/json', $this->transport->lastRequest()->headers['Content-Type']);
    }

    public function testHeaderOverridesAreCaseInsensitive(): void
    {
        $this->transport->queue(Response::json(['total' => 1]));

        $this->client->xml();

        $headers = $this->transport->lastRequest()->headers;

        // `Content-Type` və `content-type` PHP massivində ayrı açarlardır, HTTP-də yox.
        $this->assertCount(2, $headers);
        $this->assertSame('application/xml', $headers['Content-Type']);
    }

    public function testQueryParametersArePassedThrough(): void
    {
        $this->transport->queue(Response::json([]));

        $this->client->search('kitab', 3);

        $this->assertSame(['q' => 'kitab', 'page' => 3], $this->transport->lastRequest()->query);
    }

    public function testDtoBodyIsSerialisedWithoutNulls(): void
    {
        $this->transport->queue(Response::json(['total' => 1]));

        $this->client->create(Sample::from(['name' => 'abc']));

        $this->assertSame(
            ['namE_OF_THING' => 'abc', 'children' => []],
            $this->transport->lastRequest()->body,
        );
    }

    public function testListBodyProducesAJsonArray(): void
    {
        $this->transport->queue(Response::json(['total' => 2]));

        $this->client->replace([new Child('a'), new Child('b')]);

        /** @var array<array-key, mixed> $body */
        $body = $this->transport->lastRequest()->body;

        $this->assertIsArray($body);
        $this->assertTrue(array_is_list($body));
        $this->assertSame([['childNamE' => 'a'], ['childNamE' => 'b']], $body);
        $this->assertTrue($this->transport->lastRequest()->bodyIsList());
    }

    public function testEmptyListBodyStaysAList(): void
    {
        $this->transport->queue(Response::json(['total' => 0]));

        $this->client->replace([]);

        // Boş toplu sorğu JSON-a `[]` kimi getməlidir, `{}` kimi yox.
        $this->assertSame([], $this->transport->lastRequest()->body);
        $this->assertTrue($this->transport->lastRequest()->bodyIsList());
    }

    public function testAllNullDtoBodyBecomesAnObjectNotAnEmptyArray(): void
    {
        $this->transport->queue(Response::json(['total' => 0]));

        // Bütün field-ləri `null` olan DTO siyahı elementi kimi `{}` olmalıdır,
        // `[]` kimi yox — əks halda API obyekt əvəzinə massiv görür.
        $this->client->blanks([Blank::from([])]);

        /** @var array<array-key, mixed> $body */
        $body = $this->transport->lastRequest()->body;

        $this->assertIsArray($body);
        $this->assertArrayHasKey(0, $body);
        $this->assertInstanceOf(stdClass::class, $body[0]);
        $this->assertSame('[{}]', (string) json_encode($body));
    }

    public function testResponseIsHydratedIntoADto(): void
    {
        $this->transport->queue(Response::json([
            'namE_OF_THING' => 'abc',
            'children' => [['childNamE' => 'kid']],
        ]));

        $sample = $this->client->fetch('res-1');

        $this->assertSame('abc', $sample->name);
        $this->assertSame('kid', $sample->children[0]->name);
    }

    public function testListResponseIsHydrated(): void
    {
        $this->transport->queue(Response::json([['childNamE' => 'a'], ['childNamE' => 'b']]));

        $children = $this->client->search('x');

        $this->assertCount(2, $children);
        $this->assertInstanceOf(Child::class, $children[0]);
        $this->assertSame('a', $children[0]->name);
    }

    public function testEveryRequestIsRecorded(): void
    {
        $this->transport->queue(Response::json([]), Response::json([]));

        $this->client->search('a');
        $this->client->search('b');

        $this->assertSame(2, $this->transport->count());
        $this->assertCount(2, $this->transport->requests());
    }

    public function testDeleteSendsNoBody(): void
    {
        $this->transport->queue(Response::json(['total' => 0]));

        $this->client->remove('res-1');

        $this->assertSame('DELETE', $this->transport->lastRequest()->method);
        $this->assertNull($this->transport->lastRequest()->body);
    }
}
