<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Balans hesabı — müştərinin mağaza krediti, ödəniş metodunun hesabı və s.
 *
 * Məbləğ **sətir** saxlanılır: servis onu JSON ədədi kimi qaytara bilər, `float`-a
 * çevirmək isə qəpik dəqiqliyini itirərdi.
 */
final readonly class Balance extends Data
{
    /**
     * @param int|null $id Balansın IDsi.
     * @param string|null $systemType Sistem növü.
     * @param int|null $venueId Aid olduğu filial.
     * @param string|null $name Balansın adı.
     * @param string|null $description Təsviri.
     * @param string|null $type Balansın növü.
     * @param string|null $amount Balansdakı məbləğ.
     * @param int|null $position Sıralamadakı mövqe.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        #[Field(name: 'system_type')]
        public ?string $systemType = null,
        #[Field(name: 'venue_id')]
        public ?int $venueId = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $type = null,
        public ?string $amount = null,
        public ?int $position = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }
}
