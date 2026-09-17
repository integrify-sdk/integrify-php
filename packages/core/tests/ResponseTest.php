<?php

declare(strict_types=1);

namespace Integrify\Tests;

use Integrify\Response;
use Integrify\Tests\Fixtures\Child;
use PHPUnit\Framework\TestCase;

/**
 * `Response` — parse etmə və DTO-ya çevirmə.
 */
final class ResponseTest extends TestCase
{
    public function testSuccessIsDeterminedByStatusCode(): void
    {
        $this->assertTrue((new Response(200, [], ''))->isSuccessful());
        $this->assertTrue((new Response(302, [], ''))->isSuccessful());
        $this->assertFalse((new Response(400, [], ''))->isSuccessful());
        $this->assertFalse((new Response(500, [], ''))->isSuccessful());
    }

    public function testNonJsonBodyBecomesAnEmptyArrayInsteadOfThrowing(): void
    {
        $this->assertSame([], (new Response(502, [], '<html>gateway error</html>'))->toArray());
    }

    public function testEmptyBodyBecomesAnEmptyArray(): void
    {
        $this->assertSame([], (new Response(200, [], '   '))->toArray());
    }

    public function testScalarJsonBodyBecomesAnEmptyArray(): void
    {
        $this->assertSame([], (new Response(200, [], '"just a string"'))->toArray());
    }

    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $response = new Response(200, ['Content-Type' => ['application/json']], '{}');

        $this->assertSame('application/json', $response->header('content-type'));
        $this->assertNull($response->header('X-Missing'));
    }

    public function testToHydratesADto(): void
    {
        $child = Response::json(['childNamE' => 'kid', 'childId' => 7])->to(Child::class);

        $this->assertSame('kid', $child->name);
        $this->assertSame(7, $child->id);
    }

    public function testToListHydratesEveryItem(): void
    {
        $children = Response::json([['childNamE' => 'a'], ['childNamE' => 'b']])->toList(Child::class);

        $this->assertCount(2, $children);
        $this->assertSame('b', $children[1]->name);
    }

    public function testToListOnAnEmptyBodyReturnsAnEmptyList(): void
    {
        $this->assertSame([], Response::json([])->toList(Child::class));
        $this->assertSame([], (new Response(200, [], ''))->toList(Child::class));
    }

    public function testJsonHelperEncodesUnicodeLiterally(): void
    {
        $this->assertStringContainsString('Kitablar', Response::json(['name' => 'Kitablar'])->body);
    }
}
