<?php

declare(strict_types=1);

namespace Integrify\Dto;

use Integrify\Exception\ValidationFailed;
use JsonSerializable;

/**
 * Bütün sorğu və cavab DTO-larının baza class-ı.
 *
 * DTO-lar `readonly` class kimi, konstruktorda promote olunmuş property-lərlə yazılır;
 * API-dəki ad və validasiya qaydaları `#[Field]` atributu ilə verilir:
 *
 * ```php
 * final readonly class GoodsItem extends Data
 * {
 *     public function __construct(
 *         #[Field(name: 'namE_OF_GOODS', maxLength: 500)]
 *         public string $name,
 *         #[Field(name: 'goodS_ID')]
 *         public ?string $categoryId = null,
 *     ) {
 *     }
 * }
 * ```
 *
 * Həm PHP-dəki property adı, həm də API-dəki ad qəbul olunur, ona görə
 * `GoodsItem::from(['name' => 'Kitab'])` və `GoodsItem::from(['namE_OF_GOODS' => 'Kitab'])`
 * eyni nəticəni verir.
 */
abstract readonly class Data implements JsonSerializable
{
    /**
     * Massivdən DTO qurur və validasiya edir.
     *
     * @param array<array-key, mixed> $input
     *
     * @throws ValidationFailed
     */
    public static function from(array $input): static
    {
        /** @var static */
        return Mapper::hydrate(static::class, $input);
    }

    /**
     * Massiv və ya hazır DTO qəbul edib DTO qaytarır.
     *
     * @param static|array<array-key, mixed> $value
     *
     * @throws ValidationFailed
     */
    public static function wrap(self|array $value): static
    {
        if ($value instanceof static) {
            return $value;
        }

        if ($value instanceof self) {
            /** @var array<array-key, mixed> $input */
            $input = $value->toArray();

            return static::from($input);
        }

        return static::from($value);
    }

    /**
     * Massiv (və ya DTO) siyahısını DTO siyahısına çevirir.
     *
     * @param iterable<array-key, static|array<array-key, mixed>> $values
     *
     * @return list<static>
     *
     * @throws ValidationFailed
     */
    public static function wrapAll(iterable $values): array
    {
        $items = [];

        foreach ($values as $value) {
            $items[] = static::wrap($value);
        }

        return $items;
    }

    /**
     * DTO-nu API-nin gözlədiyi massivə çevirir.
     *
     * @param bool $skipNull `null` dəyərli field-ləri nəticəyə salmır.
     * @param list<string> $only Yalnız bu property-lər daxil edilir (boşdursa, hamısı).
     *
     * @return array<string, mixed>
     */
    public function toArray(bool $skipNull = false, array $only = []): array
    {
        return Mapper::dehydrate($this, skipNull: $skipNull, only: $only);
    }

    /**
     * Property adlarının siyahısı — elan olunma sırasında.
     *
     * @return list<string>
     */
    public static function properties(): array
    {
        return array_keys(Mapper::describe(static::class));
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
