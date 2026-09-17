<?php

declare(strict_types=1);

namespace Integrify\Tests;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\Environment;
use Integrify\Exception\MissingConfiguration;
use Integrify\Exception\RequestFailed;
use Integrify\Exception\ValidationFailed;
use Integrify\Http\HttpTransport;
use Integrify\Http\Request;
use Integrify\Response;
use Integrify\Tests\Fixtures\Blank;
use Integrify\Tests\Fixtures\Child;
use Integrify\Tests\Fixtures\Colour;
use Integrify\Tests\Fixtures\Counter;
use Integrify\Tests\Fixtures\Level;
use Integrify\Tests\Fixtures\Shape;
use Integrify\Tests\Fixtures\Size;
use Integrify\Tests\Fixtures\StubHttpClient;
use Integrify\Tests\Fixtures\Tagged;
use JsonException;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;

/**
 * Kod nəzərdən keçirilərkən tapılmış xətaların təkrarlanmaması üçün testlər.
 *
 * Hər test bir konkret səhvə uyğundur; adı nəyin sınmışdığını izah edir.
 */
final class RegressionTest extends TestCase
{
    /**
     * Backing tipinə uyğun gəlməyən dəyər `tryFrom()`-a ötürüləndə `strict_types`
     * səbəbindən `TypeError` atılırdı və DTO qatından kənara çıxırdı.
     *
     * İndi çevirmə mərkəzləşib: yararsız dəyər `ValidationFailed` verir.
     */
    public function testInvalidEnumArrayItemFailsValidationInsteadOfFatal(): void
    {
        $this->expectException(ValidationFailed::class);

        Tagged::from(['levels' => ['not-a-level']]);
    }

    public function testFloatEnumArrayItemFailsValidationInsteadOfFatal(): void
    {
        $this->expectException(ValidationFailed::class);

        Tagged::from(['levels' => [1.9]]);
    }

    /**
     * Skalyar və massiv yolları eyni qaydalarla işləməlidir: JSON-da ədəd bəzən
     * sətir kimi gəlir, ona görə `'1'` int-backed enum üçün qəbul olunur.
     */
    public function testNumericStringIsAcceptedConsistentlyInBothPaths(): void
    {
        $this->assertSame([Level::Low], Tagged::from(['levels' => ['1']])->levels);
        $this->assertSame(Level::Low, Tagged::from(['levels' => [], 'level' => '1'])->level);
    }

    public function testEnumArrayWithRightTypeStillWorks(): void
    {
        $tagged = Tagged::from(['levels' => [1, 2]]);

        $this->assertSame([Level::Low, Level::High], $tagged->levels);
    }

    public function testStringBackedEnumArrayRejectsIntegers(): void
    {
        $this->expectException(ValidationFailed::class);

        Tagged::from(['levels' => [], 'colours' => [1]]);
    }

    /**
     * `is_numeric()` kəsr və eksponensial dəyərləri qəbul edir, `(int)` isə onları
     * səssizcə kəsirdi — nəticədə `1.9` qonşu enum case-inə düşürdü.
     */
    public function testFractionalValueIsNotSilentlyTruncatedToAnEnumCase(): void
    {
        $this->expectException(ValidationFailed::class);

        Tagged::from(['levels' => [], 'level' => 1.9]);
    }

    public function testFractionalStringIsRejectedForIntegerField(): void
    {
        $this->expectException(ValidationFailed::class);

        Counter::from(['total' => '1.9']);
    }

    /**
     * `(int)` `PHP_INT_MAX`-dan böyük sətirləri "yapışdırırdı".
     */
    public function testIntegerOverflowIsRejectedInsteadOfSaturating(): void
    {
        $this->expectException(ValidationFailed::class);

        Counter::from(['total' => '99999999999999999999']);
    }

    public function testIntegerBoundaryStillParses(): void
    {
        $this->assertSame(PHP_INT_MAX, Counter::from(['total' => (string) PHP_INT_MAX])->total);
        $this->assertSame(-5, Counter::from(['total' => '-5'])->total);
    }

    /**
     * `null`-lar atılanda siyahıda "deşik" qalırdı (`[0 => .., 2 => ..]`), bu isə
     * JSON-a obyekt kimi yazılırdı.
     */
    public function testSkippingNullsKeepsAListAList(): void
    {
        $shape = new Shape(notes: ['a', null, 'c']);

        $this->assertSame(['notes' => ['a', 'c']], $shape->toArray(skipNull: true));
        $this->assertSame('{"notes":["a","c"]}', (string) json_encode($shape->toArray(skipNull: true)));
    }

    /**
     * Bütün field-ləri `null` olan nested DTO `[]` olurdu və JSON-a `[]` kimi
     * yazılırdı, halbuki API `{}` gözləyir.
     */
    public function testAllNullNestedDtoSerialisesAsAnObject(): void
    {
        $shape = new Shape(notes: [], blank: new Blank());
        $body = $shape->toArray(skipNull: true);

        $this->assertInstanceOf(stdClass::class, $body['blank']);
        $this->assertSame('{"notes":[],"blank":{}}', (string) json_encode($body));
    }

    /**
     * `iterable` tipli field `#[Field(of: ...)]`-i nəzərə almırdı.
     */
    public function testIterableFieldHonoursTheItemType(): void
    {
        $tagged = Tagged::from(['levels' => [], 'stream' => [['childNamE' => 'kid']]]);

        /** @var array<array-key, mixed> $stream */
        $stream = $tagged->stream;

        $this->assertIsArray($stream);
        $this->assertInstanceOf(Child::class, $stream[0]);
    }

    /**
     * Backing-siz enum `toArray()`-də adına görə yazılırdı, lakin geri oxuna bilmirdi.
     */
    public function testPureEnumRoundTrips(): void
    {
        $original = new Shape(notes: [], size: Size::Large);
        $restored = Shape::from($original->toArray(skipNull: true));

        $this->assertSame(Size::Large, $restored->size);
    }

    public function testUnknownPureEnumCaseIsRejected(): void
    {
        $this->expectException(ValidationFailed::class);

        Shape::from(['notes' => [], 'size' => 'Enormous']);
    }

    /**
     * Massiv olmayan elementlər səssizcə atılırdı — data itirdi.
     */
    public function testMalformedListElementIsReportedNotDropped(): void
    {
        $response = Response::json([['childNamE' => 'a'], 'oops']);

        $this->expectException(ValidationFailed::class);

        $response->toList(Child::class);
    }

    /**
     * `header()` sətir dəyərli header-də ilk simvolu qaytarırdı.
     */
    public function testHeaderLookupHandlesStringValues(): void
    {
        // PSR-7 header-ləri massiv verir, lakin `Response` əl ilə də qurula bilər —
        // sətir dəyəri ilk simvola kəsilməməlidir.
        $response = new Response(200, self::looseHeaders(), '{}');

        $this->assertSame('application/json', $response->header('Content-Type'));
    }

    /**
     * Sxemə uyğun gəlməyən (sətir dəyərli) header dəsti qaytarır.
     *
     * `Response::$headers` PSR-7 formasını (`array<string, array<string>>`)
     * gözləyir, lakin obyekt əl ilə də qurula bilər — `mixed` üzərindən keçirik ki,
     * bu qəsdən "səhv" forma statik analizi çaşdırmasın.
     *
     * @return array<string, array<string>>
     */
    private static function looseHeaders(): array
    {
        /** @var mixed $headers */
        $headers = ['Content-Type' => 'application/json'];

        /** @var array<string, array<string>> $headers */
        return $headers;
    }

    public function testHeaderLookupHandlesArrayValues(): void
    {
        $response = new Response(200, ['Content-Type' => ['application/json']], '{}');

        $this->assertSame('application/json', $response->header('content-type'));
    }

    /**
     * Enum-un `int` field-ə çevrilməsi (bəzi endpoint-lər ədəd gözləyir).
     */
    public function testBackedEnumCastsToIntegerField(): void
    {
        $this->assertSame(2, Counter::from(['total' => Level::High])->total);
    }

    public function testStringBackedEnumCastsToStringField(): void
    {
        $this->assertSame('red', Shape::from(['notes' => [], 'label' => Colour::Red])->label);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 2026-09 nəzərdən keçirmə
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Union tipində ilk variantın xətası bütün union-un xətası kimi qalxırdı:
     * `Circle|Square` field-inə düzgün `Square` versən də `Circle`-ın şikayəti
     * atılırdı, çünki `convertObject()` NO_MATCH qaytarmaq əvəzinə exception atır.
     */
    public function testUnionTypeTriesEveryMemberNotJustTheFirst(): void
    {
        $box = UnionBox::from(['shape' => ['side' => 2.0]]);

        $this->assertInstanceOf(UnionSquare::class, $box->shape);
    }

    public function testUnionTypeStillReportsAFailureWhenNoMemberMatches(): void
    {
        $this->expectException(ValidationFailed::class);

        UnionBox::from(['shape' => ['nothing' => 1]]);
    }

    /**
     * `abs($value) <= PHP_INT_MAX` müqayisəsində `PHP_INT_MAX` float-a çevrilib
     * 2^63 olurdu, yəni 2^63 özü testdən keçib `(int)`-də `PHP_INT_MIN`-ə dönürdü.
     * `json_decode` int diapazonundan kənar hər ədədi float verdiyi üçün bu,
     * real payload-larda baş verirdi.
     */
    public function testFloatJustPastIntMaxIsRejectedNotWrapped(): void
    {
        $this->expectException(ValidationFailed::class);

        /** @var array<string, mixed> $payload */
        $payload = json_decode('{"total": 9223372036854775808}', true);

        Counter::from($payload);
    }

    /**
     * `NAN`/`INF` fast path-dən keçib DTO-ya düşürdü; `min`/`max` müqayisələri
     * onlar üçün həmişə `false` olduğu üçün validasiya da tutmurdu, sonra isə
     * `json_encode()` bütün DTO üçün `false` qaytarırdı.
     */
    public function testNonFiniteFloatIsRejected(): void
    {
        $this->expectException(ValidationFailed::class);

        Measure::from(['value' => NAN]);
    }

    public function testInfiniteFloatIsRejected(): void
    {
        $this->expectException(ValidationFailed::class);

        Measure::from(['value' => INF]);
    }

    /**
     * `max` massivdə element sayı deməkdir, lakin yoxlama yalnız `is_int($max)`
     * halında işləyirdi — `max: 2.0` səssizcə limiti söndürürdü.
     */
    public function testFloatMaxStillLimitsArrayLength(): void
    {
        $this->expectException(ValidationFailed::class);

        FloatCapped::from(['items' => [1, 2, 3]]);
    }

    /**
     * `get_object_vars()` `Mapper`-in scope-undan yalnız public property-ləri görür;
     * `?? null` isə "görünmür"-ü "null-dır" kimi yazırdı və dəyər itirdi.
     */
    public function testNonPublicPromotedPropertyIsNotSerialisedAsNull(): void
    {
        $dto = Hidden::from(['id' => 'x', 'token' => 'abc']);

        $this->assertSame(['id' => 'x', 'token' => 'abc'], $dto->toArray());
    }

    /**
     * `toEnum()` tanınmayan **dəyər** üçün özü atırdı, ona görə `castArray()`-in
     * indeks prefiksi yalnız yanlış **tip** halında əlavə olunurdu.
     */
    public function testInvalidEnumValueInAnArrayNamesItsIndex(): void
    {
        try {
            Tagged::from(['levels' => [1, 2, 99]]);
            $this->fail('expected ValidationFailed');
        } catch (ValidationFailed $failure) {
            $this->assertStringContainsString('[2]', $failure->getMessage());
        }
    }

    /**
     * Yazılış xətası olan `of:` (və ya interfeys adı) bütün element validasiyasını
     * səssizcə söndürürdü. İndi proqramçı xətası kimi dərhal bildirilir.
     */
    public function testFieldOfMustNameADtoOrEnum(): void
    {
        $this->expectException(LogicException::class);

        BadOf::from(['items' => ['whatever']]);
    }

    /**
     * Intersection və DNF tipləri reflection-dan oxunmurdu, ona görə çevirmə
     * edilmirdi və konstruktor xam `TypeError` atırdı.
     */
    public function testIntersectionTypeFailsValidationInsteadOfTypeError(): void
    {
        $this->expectException(ValidationFailed::class);

        Intersecting::from(['thing' => 'not an object']);
    }

    /**
     * `int` backing-li enum sətir field-ində rədd olunurdu, halbuki adi `int`
     * qəbul edilirdi (`2` -> `'2'`).
     */
    public function testIntegerBackedEnumCastsToStringField(): void
    {
        $this->assertSame('2', Shape::from(['notes' => [], 'label' => Level::High])->label);
    }

    /**
     * Kök səviyyədə JSON **obyekt** gələndə açarlar səssizcə atılıb siyahı
     * uydurulurdu.
     */
    public function testToListRefusesAJsonObject(): void
    {
        $this->expectException(ValidationFailed::class);

        (new Response(200, [], '{"first":{"childNamE":"a"},"second":{"childNamE":"b"}}'))
            ->toList(Child::class);
    }

    /**
     * Fragment url-in sonunda olmalıdır; query sadəcə sona yapışdırılanda
     * `#frag?a=1` alınırdı və `a=1` heç vaxt məftilə çıxmırdı.
     */
    public function testQueryIsInsertedBeforeTheFragment(): void
    {
        $factory = new Psr17Factory();
        $client = new StubHttpClient([new PsrResponse(200, [], '{}')]);

        (new HttpTransport($client, $factory, $factory))
            ->send(new Request('GET', 'https://a.test/x#frag', null, ['a' => 1]));

        $this->assertSame('https://a.test/x?a=1#frag', (string) $client->lastRequest()->getUri());
    }

    /**
     * `APP_ENV=production` (Laravel-in default-u) `Test`-ə düşürdü, yəni tətbiq
     * xəbərsiz sandbox-a sorğu atırdı.
     */
    public function testProductionAliasesResolveToProd(): void
    {
        $this->assertTrue(Environment::parse('production')->isProduction());
        $this->assertTrue(Environment::parse('live')->isProduction());
        $this->assertTrue(Environment::parse(' PROD ')->isProduction());
        $this->assertFalse(Environment::parse('sandbox')->isProduction());
    }

    public function testUnknownEnvironmentCanBeMadeFatal(): void
    {
        $this->expectException(MissingConfiguration::class);

        Environment::parse('prd', strict: true);
    }

    /**
     * PSR-18 yalnız `ClientExceptionInterface` vəd edir, lakin middleware-lər
     * başqa exception-lar da ata bilir — onlar kitabxanadan kənara sızırdı.
     */
    public function testForeignClientExceptionIsWrapped(): void
    {
        $factory = new Psr17Factory();

        $this->expectException(RequestFailed::class);

        (new HttpTransport(new RudeHttpClient(), $factory, $factory))
            ->send(new Request('GET', 'https://a.test/x'));
    }

    /**
     * `encode()` artıq dolğun `RequestFailed` qururdu, lakin çağıran onu yenidən
     * bürüyürdü: mesaj ikiləşir və kodlama xətası şəbəkə xətası kimi görünürdü.
     */
    public function testEncodingFailureIsNotRewrappedAsATransportError(): void
    {
        $factory = new Psr17Factory();

        try {
            (new HttpTransport(new StubHttpClient(), $factory, $factory))
                ->send(new Request('POST', 'https://a.test/x', ['amount' => INF]));
            $this->fail('expected RequestFailed');
        } catch (RequestFailed $failure) {
            $this->assertInstanceOf(JsonException::class, $failure->getPrevious());
        }
    }

    /**
     * Təkrarlana bilən header-lərin (məs., `Set-Cookie`) yalnız birinci dəyərinə
     * çatmaq mümkün idi.
     */
    public function testRepeatedHeaderValuesAreReachable(): void
    {
        $response = new Response(200, ['Set-Cookie' => ['a=1', 'b=2']], '');

        $this->assertSame(['a=1', 'b=2'], $response->headerValues('set-cookie'));
        $this->assertSame('a=1', $response->header('set-cookie'));
    }
}

/** @internal */
final readonly class UnionCircle extends Data
{
    public function __construct(public float $radius)
    {
    }
}

/** @internal */
final readonly class UnionSquare extends Data
{
    public function __construct(public float $side)
    {
    }
}

/** @internal */
final readonly class UnionBox extends Data
{
    public function __construct(public UnionCircle|UnionSquare $shape)
    {
    }
}

/** @internal */
final readonly class Measure extends Data
{
    public function __construct(
        #[Field(min: 0, max: 100)]
        public float $value,
    ) {
    }
}

/** @internal */
final readonly class FloatCapped extends Data
{
    /** @param list<mixed> $items */
    public function __construct(
        #[Field(max: 2.0)]
        public array $items = [],
    ) {
    }
}

/** @internal */
final readonly class Hidden extends Data
{
    public function __construct(
        public string $id,
        protected string $token = '',
    ) {
    }
}

/** @internal */
interface Tagging
{
}

/** @internal */
final readonly class BadOf extends Data
{
    /** @param list<mixed> $items */
    public function __construct(
        #[Field(of: Tagging::class)]
        public array $items = [],
    ) {
    }
}

/** @internal */
interface Alpha
{
}

/** @internal */
interface Beta
{
}

/** @internal */
final readonly class Intersecting extends Data
{
    public function __construct(public Alpha&Beta $thing)
    {
    }
}

/** @internal */
final class RudeHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        throw new LogicException('a middleware blew up');
    }
}
