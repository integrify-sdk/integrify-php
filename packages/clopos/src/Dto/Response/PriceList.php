<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Qiymət cədvəli — filiala və ya kanala görə alternativ qiymətlər toplusu.
 */
final readonly class PriceList extends Data
{
    /**
     * @param int|null $id Cədvəlin IDsi.
     * @param string|null $name Cədvəlin adı.
     * @param string|null $description Təsviri.
     * @param bool|null $status Aktivdirmi.
     * @param list<PriceListPrice>|null $prices Cədvəldəki qiymətlər (`with[]=prices`).
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?bool $status = null,
        #[Field(of: PriceListPrice::class)]
        public ?array $prices = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
    ) {
    }
}
