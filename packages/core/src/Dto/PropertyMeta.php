<?php

declare(strict_types=1);

namespace Integrify\Dto;

use Integrify\Dto\Attribute\Field;

/**
 * Bir DTO property-si haqqında reflection-dan çıxarılmış metadata.
 *
 * Mapper-in isti yolunda olduğu üçün tez-tez lazım olan qərarlar (tək tipdirmi,
 * qaydası varmı, adı API adı ilə eynidirmi) burada əvvəlcədən hesablanır.
 *
 * @internal
 */
final readonly class PropertyMeta
{
    /**
     * @param string $property PHP-dəki property adı.
     * @param string $name API-dəki field adı.
     * @param list<string> $types Qəbul edilən tiplər (`null`-suz).
     * @param string|null $singleType Yalnız bir tip varsa, həmin tip.
     * @param bool $nullable `null` qəbul edirmi.
     * @param bool $hasDefault Default dəyəri varmı.
     * @param mixed $default Default dəyər.
     * @param bool $validates Ən azı bir validasiya qaydası varmı.
     * @param bool $nameMatchesProperty API adı property adı ilə eynidirmi.
     * @param Field $field Atributun özü.
     */
    public function __construct(
        public string $property,
        public string $name,
        public array $types,
        public ?string $singleType,
        public bool $nullable,
        public bool $hasDefault,
        public mixed $default,
        public bool $validates,
        public bool $nameMatchesProperty,
        public Field $field,
    ) {
    }
}
