<?php

declare(strict_types=1);

namespace Integrify\Dto\Attribute;

use Attribute;

/**
 * DTO property-sinin API-dəki adını və validasiya qaydalarını təyin edir.
 *
 * ```php
 * final readonly class GoodsItem extends Data
 * {
 *     public function __construct(
 *         #[Field(name: 'namE_OF_GOODS', maxLength: 500)]
 *         public string $name,
 *
 *         #[Field(name: 'goodsList', of: GoodsItem::class, max: 40)]
 *         public array $items = [],
 *     ) {
 *     }
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class Field
{
    /**
     * @param string|null $name API-nin gözlədiyi field adı. Verilməsə, property adı istifadə olunur.
     * @param class-string|null $of `array` tipli field-in element tipi (DTO və ya enum).
     * @param int|null $maxLength Sətir üçün maksimum simvol sayı.
     * @param int|null $minLength Sətir üçün minimum simvol sayı.
     * @param string|null $pattern Sətir üçün PCRE şablonu (delimiter-lərlə birlikdə).
     *     Diqqət: PCRE-də `$` sətrin sonundakı bir `\n`-i də qəbul edir, ona görə
     *     validasiya şablonlarında `$` yox, `\z` istifadə edin.
     * @param int|float|null $min Ədəd üçün minimum dəyər.
     * @param int|float|null $max Ədəd üçün maksimum dəyər, və ya massiv üçün maksimum element sayı.
     * @param int|null $minItems Massiv üçün minimum element sayı.
     * @param int|null $maxItems Massiv üçün maksimum element sayı.
     */
    public function __construct(
        public ?string $name = null,
        public ?string $of = null,
        public ?int $maxLength = null,
        public ?int $minLength = null,
        public ?string $pattern = null,
        public int|float|null $min = null,
        public int|float|null $max = null,
        public ?int $minItems = null,
        public ?int $maxItems = null,
    ) {
    }
}
