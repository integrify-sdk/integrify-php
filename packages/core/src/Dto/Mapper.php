<?php

declare(strict_types=1);

namespace Integrify\Dto;

use BackedEnum;
use Integrify\Dto\Attribute\Field;
use Integrify\Exception\ValidationFailed;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use stdClass;
use UnitEnum;

/**
 * Massiv <-> DTO çevirməsini və validasiyanı həyata keçirən reflection mapper-i.
 *
 * DTO-ların metadata-sı class başına bir dəfə hesablanıb keşlənir.
 *
 * @internal `Data`-nın implementasiya detalıdır; birbaşa istifadə üçün nəzərdə tutulmayıb.
 */
final class Mapper
{
    /** @var array<class-string<Data>, array<string, PropertyMeta>> */
    private static array $cache = [];

    /**
     * DTO-nun property metadata-sını (keşlənmiş şəkildə) qaytarır.
     *
     * @param class-string<Data> $dto
     *
     * @return array<string, PropertyMeta>
     */
    public static function describe(string $dto): array
    {
        if (isset(self::$cache[$dto])) {
            return self::$cache[$dto];
        }

        $constructor = (new ReflectionClass($dto))->getConstructor();
        $meta = [];

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $meta[$parameter->getName()] = self::describeProperty($parameter);
        }

        return self::$cache[$dto] = $meta;
    }

    /**
     * Massivdən DTO qurur və validasiya edir.
     *
     * @template T of Data
     *
     * @param class-string<T> $dto
     * @param array<array-key, mixed> $input
     *
     * @return T
     *
     * @throws ValidationFailed
     */
    public static function hydrate(string $dto, array $input): Data
    {
        $metadata = self::describe($dto);

        // Property-lər elan olunma sırasında gəzilir və hər birinə mütləq bir dəyər
        // yazılır, ona görə arqumentləri positional list kimi yığırıq — bu,
        // konstruktora named argument ötürməkdən nəzərəçarpacaq dərəcədə sürətlidir.
        $arguments = [];
        $errors = [];

        foreach ($metadata as $property => $meta) {
            $missing = false;

            if (array_key_exists($meta->name, $input)) {
                /** @var mixed $raw */
                $raw = $input[$meta->name];
            } elseif (!$meta->nameMatchesProperty && array_key_exists($property, $input)) {
                /** @var mixed $raw */
                $raw = $input[$property];
            } else {
                $raw = null;
                $missing = true;
            }

            if ($missing) {
                if ($meta->hasDefault) {
                    $arguments[] = $meta->default;

                    continue;
                }

                $errors[$property] = 'field is required';

                continue;
            }

            try {
                /** @var mixed $value */
                $value = self::cast($raw, $meta);

                if ($meta->validates) {
                    self::check($value, $meta->field);
                }

                $arguments[] = $value;
            } catch (CastFailed $failure) {
                $errors[$property] = $failure->getMessage();
            }
        }

        if ($errors !== []) {
            throw new ValidationFailed($dto, $errors);
        }

        try {
            /** @var T */
            return new $dto(...$arguments);
        } catch (\TypeError $error) {
            // `describeProperty()` intersection və DNF tiplərini oxuya bilmir, ona görə
            // belə property-lər üçün çevirmə edilmir və uyğunsuzluğu yalnız konstruktor
            // aşkar edir. Kitabxananın müqaviləsi `ValidationFailed`-dir — xam `TypeError`
            // sızmamalıdır.
            throw new ValidationFailed($dto, ['*' => $error->getMessage()]);
        }
    }

    /**
     * DTO-nu massivə çevirir.
     *
     * @param list<string> $only Yalnız bu property-lər (boşdursa, hamısı).
     *
     * @return array<string, mixed>
     */
    public static function dehydrate(Data $dto, bool $skipNull = false, array $only = []): array
    {
        $metadata = self::describe($dto::class);
        // Bir dəfə `get_object_vars()` çağırmaq hər property üçün ayrıca dinamik
        // oxumaqdan sürətlidir (DTO-ların property-ləri adətən public-dir).
        $values = get_object_vars($dto);
        $result = [];

        foreach ($metadata as $property => $meta) {
            if ($only !== [] && !in_array($property, $only, true)) {
                continue;
            }

            if (array_key_exists($property, $values)) {
                /** @var mixed $value */
                $value = $values[$property];
            } else {
                // Property public deyil (`protected`/`private` promoted parametr):
                // `get_object_vars()` onu bu scope-dan görmür. Əvvəllər burada
                // səssizcə `null` yazılırdı — dəyər itirdi. Nadir haldır, ona görə
                // bahalı yol yalnız burada seçilir.
                /** @var mixed $value */
                $value = self::readHidden($dto, $property);
            }

            if ($value === null) {
                if (!$skipNull) {
                    $result[$meta->name] = null;
                }

                continue;
            }

            $result[$meta->name] = is_scalar($value) ? $value : self::flatten($value, $skipNull);
        }

        return $result;
    }

    /**
     * `protected`/`private` promoted property-ni DTO-nun öz scope-undan oxuyur.
     */
    private static function readHidden(Data $dto, string $property): mixed
    {
        $reader = \Closure::bind(
            static fn (Data $object): mixed => $object->{$property} ?? null,
            null,
            $dto::class,
        );

        return $reader === null ? null : $reader($dto);
    }

    /**
     * Dəyəri JSON-a uyğun formaya salır.
     */
    private static function flatten(mixed $value, bool $skipNull): mixed
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if ($value instanceof Data) {
            $nested = self::dehydrate($value, skipNull: $skipNull);

            // PHP-də `[]` həm boş obyekt, həm boş siyahıdır və JSON-a `[]` kimi
            // yazılır. Bütün field-ləri `null` olan DTO API üçün `{}` olmalıdır,
            // ona görə boş nəticəni açıq şəkildə obyektə çeviririk.
            return $nested === [] ? new stdClass() : $nested;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_array($value)) {
            $wasList = array_is_list($value);
            $flattened = [];

            /** @var mixed $item */
            foreach ($value as $key => $item) {
                if ($item === null && $skipNull) {
                    continue;
                }

                $flattened[$key] = self::flatten($item, $skipNull);
            }

            // `null`-ların atılması siyahıda "deşik" qoya bilər (`[0 => .., 2 => ..]`),
            // bu isə JSON-a obyekt kimi yazılardı. Siyahı idisə, siyahı qalmalıdır.
            return $wasList ? array_values($flattened) : $flattened;
        }

        return $value;
    }

    private static function describeProperty(ReflectionParameter $parameter): PropertyMeta
    {
        $attributes = $parameter->getAttributes(Field::class);
        $field = $attributes === [] ? new Field() : $attributes[0]->newInstance();

        $declared = $parameter->getType();
        $types = [];

        if ($declared instanceof ReflectionNamedType) {
            $types = $declared->getName() === 'null' ? [] : [$declared->getName()];
        } elseif ($declared instanceof ReflectionUnionType) {
            foreach ($declared->getTypes() as $member) {
                if ($member instanceof ReflectionNamedType && $member->getName() !== 'null') {
                    $types[] = $member->getName();
                }
            }
        }

        $types = array_values(array_filter($types, static fn (string $t): bool => $t !== 'mixed'));

        $property = $parameter->getName();
        $name = $field->name ?? $property;
        $hasDefault = $parameter->isDefaultValueAvailable();

        // `of:` yalnız `Data` və ya enum ola bilər. Səhv (və ya yazılış xətası olan)
        // class adı əvvəllər səssizcə bütün element validasiyasını söndürürdü;
        // bu proqramçı xətasıdır, ona görə dərhal bildirilir.
        if ($field->of !== null
            && !is_subclass_of($field->of, Data::class)
            && !is_subclass_of($field->of, UnitEnum::class)
        ) {
            throw new \LogicException(sprintf(
                '%s::$%s: #[Field(of: %s)] must name a %s subclass or an enum.',
                $parameter->getDeclaringClass()?->getName() ?? '?',
                $property,
                $field->of,
                Data::class,
            ));
        }

        return new PropertyMeta(
            property: $property,
            name: $name,
            types: $types,
            singleType: count($types) === 1 ? $types[0] : null,
            nullable: $declared === null || $declared->allowsNull(),
            hasDefault: $hasDefault,
            default: $hasDefault ? $parameter->getDefaultValue() : null,
            validates: $field->maxLength !== null
                || $field->minLength !== null
                || $field->pattern !== null
                || $field->min !== null
                || $field->max !== null
                || $field->minItems !== null
                || $field->maxItems !== null,
            nameMatchesProperty: $name === $property,
            field: $field,
        );
    }

    /**
     * @throws CastFailed
     */
    private static function cast(mixed $value, PropertyMeta $meta): mixed
    {
        if ($value === null) {
            if (!$meta->nullable) {
                throw new CastFailed('may not be null');
            }

            return null;
        }

        if ($meta->types === []) {
            return $value;
        }

        // Fast path: DTO-ların əksəriyyəti tək tipli sətir/ədəd field-lərindən ibarətdir.
        switch ($meta->singleType) {
            case 'string':
                if (is_string($value)) {
                    return $value;
                }

                break;

            case 'int':
                if (is_int($value)) {
                    return $value;
                }

                break;

            case 'float':
                // `NAN`/`INF` JSON-a yazıla bilmir və `min`/`max` müqayisələri
                // onlar üçün həmişə `false` verir, ona görə burada kəsilir.
                if (is_float($value)) {
                    if (is_finite($value)) {
                        return $value;
                    }

                    throw new CastFailed('must be a finite number');
                }

                break;

            case 'bool':
                if (is_bool($value)) {
                    return $value;
                }

                break;
        }

        foreach ($meta->types as $type) {
            if (self::isOfType($value, $type)) {
                if ($type === 'array' || $type === 'iterable') {
                    return self::castArray($value, $meta);
                }

                if ($type === 'float' && is_float($value) && !is_finite($value)) {
                    throw new CastFailed('must be a finite number');
                }

                return $value;
            }
        }

        // Union tipi: hər variant ayrıca yoxlanılır. DTO və enum çevirmələri öz
        // `CastFailed`-lərini atır — əvvəllər bu, ilk variantda dövrəni dayandırırdı
        // və `Circle|Square` kimi union-larda ikinci variant heç vaxt sınanmırdı.
        $firstFailure = null;

        foreach ($meta->types as $type) {
            try {
                /** @var mixed $converted */
                $converted = self::convert($value, $type);
            } catch (CastFailed $failure) {
                $firstFailure ??= $failure;

                continue;
            }

            if ($converted !== CastFailed::NO_MATCH) {
                return $converted;
            }
        }

        // Tək tipli field-də çevirmənin öz mesajı daha faydalıdır
        // ("'x' is not a valid Colour" vs "expected Colour, got string").
        if ($firstFailure !== null && $meta->singleType !== null) {
            throw $firstFailure;
        }

        throw new CastFailed(sprintf(
            'expected %s, got %s',
            implode('|', $meta->types),
            get_debug_type($value),
        ));
    }

    private static function isOfType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'int' => is_int($value),
            'float' => is_float($value),
            'bool' => is_bool($value),
            'array', 'iterable' => is_array($value),
            'object' => is_object($value),
            default => $value instanceof $type,
        };
    }

    /**
     * @throws CastFailed
     */
    private static function convert(mixed $value, string $type): mixed
    {
        switch ($type) {
            case 'string':
                if (is_int($value) || is_float($value)) {
                    return (string) $value;
                }

                // `int` backing-li enum da sətrə çevrilir — `int` 2 üçün `'2'`
                // qaytarıldığı halda `Level::High` üçün qaytarılmaması uyğunsuzluq idi.
                if ($value instanceof BackedEnum) {
                    return (string) $value->value;
                }

                return CastFailed::NO_MATCH;

            case 'int':
                return self::toInt($value);

            case 'float':
                if ($value instanceof BackedEnum) {
                    return self::convert($value->value, 'float');
                }

                if (is_float($value)) {
                    return is_finite($value) ? $value : CastFailed::NO_MATCH;
                }

                if (is_int($value)) {
                    return (float) $value;
                }

                if (is_string($value) && is_numeric($value)) {
                    $parsed = (float) $value;

                    return is_finite($parsed) ? $parsed : CastFailed::NO_MATCH;
                }

                return CastFailed::NO_MATCH;

            case 'bool':
                if (is_int($value) && ($value === 0 || $value === 1)) {
                    return $value === 1;
                }

                if (is_string($value) && in_array(strtolower($value), ['true', 'false', '1', '0'], true)) {
                    return in_array(strtolower($value), ['true', '1'], true);
                }

                return CastFailed::NO_MATCH;

            case 'array':
            case 'iterable':
                return CastFailed::NO_MATCH;

            default:
                return self::convertObject($value, $type);
        }
    }

    /**
     * Dəyəri enum case-inə çevirir.
     *
     * Backed enum-un backing tipi ilə uyğun gəlməyən dəyər `tryFrom()`-a
     * ötürülsə, `strict_types` səbəbindən `TypeError` atılardı — ona görə
     * çevirmə burada, mərkəzi şəkildə edilir.
     *
     * @param class-string<UnitEnum> $enum
     *
     * @throws CastFailed
     */
    private static function toEnum(mixed $value, string $enum): mixed
    {
        if ($value instanceof $enum) {
            return $value;
        }

        // Backing-siz (pure) enum: case adına görə tapılır.
        if (!is_subclass_of($enum, BackedEnum::class)) {
            if (!is_string($value)) {
                return CastFailed::NO_MATCH;
            }

            foreach ($enum::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }

            throw new CastFailed(sprintf("'%s' is not a valid %s", $value, self::shortName($enum)));
        }

        /** @var class-string<BackedEnum> $enum */
        $backing = (string) (new ReflectionEnum($enum))->getBackingType();

        if ($backing === 'int') {
            $candidate = self::toInt($value);
        } else {
            $candidate = match (true) {
                is_string($value) => $value,
                is_int($value) => (string) $value,
                default => CastFailed::NO_MATCH,
            };
        }

        if ($candidate === CastFailed::NO_MATCH) {
            return CastFailed::NO_MATCH;
        }

        /** @var int|string $candidate */
        $case = $enum::tryFrom($candidate);

        if ($case === null) {
            throw new CastFailed(sprintf(
                "'%s' is not a valid %s",
                (string) $candidate,
                self::shortName($enum),
            ));
        }

        return $case;
    }

    /**
     * Dəyəri tam ədədə çevirir; kəsr, daşma və boşluqlu dəyərlər rədd edilir.
     */
    private static function toInt(mixed $value): mixed
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return CastFailed::NO_MATCH;
        }

        if ($value instanceof BackedEnum) {
            return self::toInt($value->value);
        }

        if (is_float($value)) {
            // `is_finite` daşmış (`INF`) dəyərləri, `floor` isə kəsrləri kənarlaşdırır.
            //
            // Diapazon yoxlaması `abs($value) <= PHP_INT_MAX` ola bilməz: müqayisədə
            // `PHP_INT_MAX` float-a çevrilir və 2^63 alınır, yəni 2^63 özü testdən
            // keçib `(int)`-də `PHP_INT_MIN`-ə dönürdü. `json_decode` int diapazonundan
            // kənar hər ədədi float verdiyi üçün bu, real payload-larda baş verirdi.
            return is_finite($value)
                && floor($value) === $value
                && $value >= -9223372036854775808.0
                && $value < 9223372036854775808.0
                ? (int) $value
                : CastFailed::NO_MATCH;
        }

        if (is_string($value)) {
            // `filter_var` `int` diapazonundan kənara çıxan sətirləri qəbul etmir,
            // `(int)` isə səssizcə `PHP_INT_MAX`-a "yapışdırardı".
            $parsed = filter_var($value, FILTER_VALIDATE_INT);

            return $parsed === false ? CastFailed::NO_MATCH : $parsed;
        }

        return CastFailed::NO_MATCH;
    }

    /**
     * @throws CastFailed
     */
    private static function convertObject(mixed $value, string $type): mixed
    {
        if (!class_exists($type) && !interface_exists($type)) {
            return CastFailed::NO_MATCH;
        }

        if (is_subclass_of($type, Data::class)) {
            if (!is_array($value)) {
                return CastFailed::NO_MATCH;
            }

            try {
                return self::hydrate($type, $value);
            } catch (ValidationFailed $failure) {
                throw new CastFailed($failure->getMessage());
            }
        }

        if (is_subclass_of($type, UnitEnum::class)) {
            /** @var class-string<UnitEnum> $type */
            return self::toEnum($value, $type);
        }

        return CastFailed::NO_MATCH;
    }

    /**
     * @throws CastFailed
     */
    private static function castArray(mixed $value, PropertyMeta $meta): mixed
    {
        if (!is_array($value)) {
            throw new CastFailed('expected array, got ' . get_debug_type($value));
        }

        $of = $meta->field->of;

        if ($of === null) {
            return $value;
        }

        // Element tipini dövrənin içində deyil, bir dəfə əvvəldən müəyyən edirik.
        $isDto = is_subclass_of($of, Data::class);
        $isEnum = !$isDto && is_subclass_of($of, UnitEnum::class);

        $items = [];

        /** @var mixed $item */
        foreach ($value as $index => $item) {
            if ($item instanceof $of) {
                $items[$index] = $item;

                continue;
            }

            if ($isDto) {
                /** @var class-string<Data> $of */
                if (!is_array($item)) {
                    throw new CastFailed(sprintf(
                        '[%s]: expected array or %s, got %s',
                        (string) $index,
                        self::shortName($of),
                        get_debug_type($item),
                    ));
                }

                try {
                    $items[$index] = self::hydrate($of, $item);
                } catch (ValidationFailed $failure) {
                    throw new CastFailed(sprintf('[%s]: %s', (string) $index, $failure->getMessage()));
                }

                continue;
            }

            if ($isEnum) {
                /** @var class-string<UnitEnum> $of */
                try {
                    $case = self::toEnum($item, $of);
                } catch (CastFailed $failure) {
                    // `toEnum()` tanınmayan **dəyər** üçün özü atır; indeks prefiksi
                    // yalnız burada əlavə oluna bilər.
                    throw new CastFailed(sprintf('[%s]: %s', (string) $index, $failure->getMessage()));
                }

                if ($case === CastFailed::NO_MATCH) {
                    throw new CastFailed(sprintf(
                        '[%s]: expected %s, got %s',
                        (string) $index,
                        self::shortName($of),
                        get_debug_type($item),
                    ));
                }

                $items[$index] = $case;

                continue;
            }

            $items[$index] = $item;
        }

        return $items;
    }

    /**
     * @throws CastFailed
     */
    private static function check(mixed $value, Field $field): void
    {
        if ($value === null) {
            return;
        }

        if (is_string($value)) {
            self::checkString($value, $field);
        }

        if (is_int($value) || is_float($value)) {
            if ($field->min !== null && $value < $field->min) {
                throw new CastFailed(sprintf('must be at least %s', (string) $field->min));
            }

            if ($field->max !== null && $value > $field->max) {
                throw new CastFailed(sprintf('must be at most %s', (string) $field->max));
            }
        }

        if (is_array($value)) {
            $count = count($value);
            // `max` massivdə element sayı deməkdir. `Field::$max` `int|float` olduğu üçün
            // `max: 2.0` əvvəllər səssizcə yoxlamanı söndürürdü.
            $maxItems = $field->maxItems ?? ($field->max === null ? null : (int) $field->max);

            if ($maxItems !== null && $count > $maxItems) {
                throw new CastFailed(sprintf('must contain at most %d items, got %d', $maxItems, $count));
            }

            if ($field->minItems !== null && $count < $field->minItems) {
                throw new CastFailed(sprintf('must contain at least %d items, got %d', $field->minItems, $count));
            }
        }
    }

    /**
     * Uzunluqlar **simvol** sayına görə ölçülür. `strlen()` yalnız fast path-dir:
     * bayt sayı simvol sayından heç vaxt kiçik olmadığı üçün ucuz yoxlama məsələni
     * həll edə bilmədikdə `mb_strlen()`-ə keçirik.
     *
     * @throws CastFailed
     */
    private static function checkString(string $value, Field $field): void
    {
        $bytes = strlen($value);

        if ($field->maxLength !== null && $bytes > $field->maxLength) {
            $length = mb_strlen($value);

            if ($length > $field->maxLength) {
                throw new CastFailed(sprintf(
                    'must be at most %d characters, got %d',
                    $field->maxLength,
                    $length,
                ));
            }
        }

        if ($field->minLength !== null) {
            $tooShort = $field->minLength === 1
                ? $value === ''
                : ($bytes < $field->minLength || mb_strlen($value) < $field->minLength);

            if ($tooShort) {
                throw new CastFailed(sprintf(
                    'must be at least %d characters, got %d',
                    $field->minLength,
                    mb_strlen($value),
                ));
            }
        }

        if ($field->pattern !== null && preg_match($field->pattern, $value) !== 1) {
            throw new CastFailed(sprintf('does not match %s', $field->pattern));
        }
    }

    private static function shortName(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }
}
