<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Clopos\Enum\DiscountType;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Müştəri qrupu — qrupa bağlı endirim qaydası ilə.
 */
final readonly class CustomerGroup extends Data
{
    /**
     * @param int|null $id Qrupun IDsi.
     * @param string|null $name Qrupun adı.
     * @param int|null $discountType Endirimin növü. Enum üçün `discountType()`.
     * @param int|null $discountValue Endirimin dəyəri — `Percentage` üçün faiz, `Fixed` üçün məbləğ.
     * @param string|null $systemType Sistem növü.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        #[Field(name: 'discount_type')]
        public ?int $discountType = null,
        #[Field(name: 'discount_value')]
        public ?int $discountValue = null,
        #[Field(name: 'system_type')]
        public ?string $systemType = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }

    /**
     * `discountType`-ın enum qarşılığı, tanınmayan dəyər üçün `null`.
     */
    public function discountType(): ?DiscountType
    {
        return $this->discountType === null ? null : DiscountType::tryFrom($this->discountType);
    }
}
