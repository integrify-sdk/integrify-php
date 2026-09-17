<?php

declare(strict_types=1);

namespace Integrify\Tests;

use Integrify\Exception\IntegrifyException;
use Integrify\Exception\ValidationFailed;
use Integrify\Tests\Fixtures\Child;
use Integrify\Tests\Fixtures\Colour;
use Integrify\Tests\Fixtures\Level;
use Integrify\Tests\Fixtures\Sample;
use PHPUnit\Framework\TestCase;

/**
 * DTO mapping, alias və validasiya testləri.
 */
final class DataTest extends TestCase
{
    public function testHydratesByApiName(): void
    {
        $this->assertSame('abc', Sample::from(['namE_OF_THING' => 'abc'])->name);
    }

    public function testHydratesByPropertyName(): void
    {
        $this->assertSame('abc', Sample::from(['name' => 'abc'])->name);
    }

    public function testApiNameWinsOverPropertyName(): void
    {
        $this->assertSame('yes', Sample::from(['namE_OF_THING' => 'yes', 'name' => 'no'])->name);
    }

    public function testBuildsNestedDtos(): void
    {
        $sample = Sample::from([
            'name' => 'abc',
            'children' => [
                ['childNamE' => 'first', 'childId' => 1],
                ['name' => 'second'],
            ],
        ]);

        $this->assertCount(2, $sample->children);
        $this->assertInstanceOf(Child::class, $sample->children[0]);
        $this->assertSame('first', $sample->children[0]->name);
        $this->assertSame(1, $sample->children[0]->id);
        $this->assertNull($sample->children[1]->id);
    }

    public function testAcceptsAlreadyBuiltNestedDtos(): void
    {
        $sample = Sample::from(['name' => 'abc', 'children' => [new Child('first')]]);

        $this->assertSame('first', $sample->children[0]->name);
    }

    public function testCastsBackedEnums(): void
    {
        $sample = Sample::from(['name' => 'abc', 'colour' => 'blue', 'level' => 2]);

        $this->assertSame(Colour::Blue, $sample->colour);
        $this->assertSame(Level::High, $sample->level);
    }

    public function testRejectsUnknownEnumValue(): void
    {
        $this->expectException(ValidationFailed::class);

        Sample::from(['name' => 'abc', 'colour' => 'green']);
    }

    public function testCastsEnumToScalarWhenTargetIsInt(): void
    {
        // Bəzi endpoint-lər eyni anlayışı ədəd kimi gözləyir.
        $this->assertSame(2, Fixtures\Counter::from(['total' => Level::High])->total);
    }

    public function testCoercesScalarTypes(): void
    {
        $sample = Sample::from(['name' => 'abc', 'score' => '7', 'active' => 1]);

        $this->assertSame(7, $sample->score);
        $this->assertTrue($sample->active);
    }

    public function testRequiredFieldIsReported(): void
    {
        try {
            Sample::from([]);
            $this->fail('Expected a ValidationFailed exception.');
        } catch (ValidationFailed $failure) {
            $this->assertSame(['name' => 'field is required'], $failure->errors);
        }
    }

    public function testCollectsEveryError(): void
    {
        try {
            Sample::from(['name' => 'far too long', 'score' => 99, 'country' => 'aze']);
            $this->fail('Expected a ValidationFailed exception.');
        } catch (ValidationFailed $failure) {
            $this->assertCount(3, $failure->errors);
            $this->assertArrayHasKey('name', $failure->errors);
            $this->assertArrayHasKey('score', $failure->errors);
            $this->assertArrayHasKey('country', $failure->errors);
        }
    }

    public function testValidationFailureIsAnIntegrifyException(): void
    {
        $this->expectException(IntegrifyException::class);

        Sample::from([]);
    }

    public function testMinLengthIsEnforced(): void
    {
        $this->expectException(ValidationFailed::class);

        Sample::from(['name' => 'a']);
    }

    /**
     * Uzunluqlar bayt yox, simvol sayına görə ölçülür: `'əəəəə'` 5 simvol,
     * lakin 10 baytdır və `maxLength: 5`-i keçməməlidir.
     */
    public function testLengthsAreCountedInCharactersNotBytes(): void
    {
        $sample = Sample::from(['name' => 'əəəəə']);

        $this->assertSame('əəəəə', $sample->name);
        $this->assertSame(10, strlen($sample->name));
    }

    public function testMultibyteStringOverMaxLengthIsRejected(): void
    {
        $this->expectException(ValidationFailed::class);

        Sample::from(['name' => 'əəəəəə']);
    }

    public function testMultibyteStringUnderMinLengthIsRejected(): void
    {
        $this->expectException(ValidationFailed::class);

        Sample::from(['name' => 'ə']);
    }

    public function testMultibyteStringAtMinLengthIsAccepted(): void
    {
        $this->assertSame('əə', Sample::from(['name' => 'əə'])->name);
    }

    public function testMaxItemsIsEnforced(): void
    {
        $this->expectException(ValidationFailed::class);

        Sample::from([
            'name' => 'abc',
            'children' => [['name' => 'a'], ['name' => 'b'], ['name' => 'c']],
        ]);
    }

    public function testNestedErrorMentionsIndex(): void
    {
        try {
            Sample::from(['name' => 'abc', 'children' => [['name' => 'way too long']]]);
            $this->fail('Expected a ValidationFailed exception.');
        } catch (ValidationFailed $failure) {
            $this->assertStringContainsString('[0]', $failure->errors['children']);
        }
    }

    public function testNullOnNonNullableFieldIsRejected(): void
    {
        $this->expectException(ValidationFailed::class);

        Sample::from(['name' => null]);
    }

    public function testToArrayUsesApiNamesAndCanSkipNulls(): void
    {
        $sample = Sample::from([
            'name' => 'abc',
            'children' => [['name' => 'kid']],
            'colour' => 'red',
        ]);

        $this->assertSame([
            'namE_OF_THING' => 'abc',
            'children' => [['childNamE' => 'kid']],
            'colour' => 'red',
        ], $sample->toArray(skipNull: true));
    }

    public function testToArrayCanBeRestrictedToCertainProperties(): void
    {
        $sample = Sample::from(['name' => 'abc', 'score' => 3]);

        $this->assertSame(['namE_OF_THING' => 'abc'], $sample->toArray(only: ['name']));
    }

    public function testJsonSerialisation(): void
    {
        $sample = Sample::from(['name' => 'abc', 'level' => 1]);

        $this->assertSame(
            '{"namE_OF_THING":"abc","children":[],"colour":null,"level":1,"score":null,"active":null,"country":null}',
            (string) json_encode($sample),
        );
    }

    public function testWrapAcceptsBothArraysAndDtos(): void
    {
        $fromArray = Child::wrap(['name' => 'kid']);

        $this->assertSame($fromArray, Child::wrap($fromArray));
        $this->assertSame('kid', $fromArray->name);
    }

    public function testWrapAllBuildsEveryItem(): void
    {
        $children = Child::wrapAll([['name' => 'a'], new Child('b')]);

        $this->assertCount(2, $children);
        $this->assertSame('a', $children[0]->name);
        $this->assertSame('b', $children[1]->name);
    }

    public function testPropertiesKeepDeclarationOrder(): void
    {
        $this->assertSame(
            ['name', 'children', 'colour', 'level', 'score', 'active', 'country'],
            Sample::properties(),
        );
    }
}
