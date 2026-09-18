<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Modifikator qrupu — məs. "Acılıq dərəcəsi".
 *
 * `type` seçim qaydasıdır: `1` tək seçim, `0` çox seçim. Neçə seçim edilə biləcəyini
 * `minSelect` və `maxSelect` müəyyən edir.
 */
final readonly class ModifierGroup extends Data
{
    /**
     * @param int|null $id Qrupun IDsi.
     * @param string|null $name Qrupun adı.
     * @param int|null $type Seçim qaydası: `1` tək, `0` çox.
     * @param int|null $minSelect Minimum seçim sayı.
     * @param int|null $maxSelect Maksimum seçim sayı.
     * @param list<Modifier>|null $modifiers Qrupdakı modifikatorlar.
     * @param bool|null $adjustToPortion Porsiya ölçüsünə uyğunlaşdırılırmı.
     * @param bool|null $status Aktivlik.
     * @param array<string, mixed>|null $meta Əlavə parametrlər.
     * @param array<string, mixed>|null $pivot Məhsulla əlaqənin məlumatları.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?int $type = null,
        #[Field(name: 'min_select')]
        public ?int $minSelect = null,
        #[Field(name: 'max_select')]
        public ?int $maxSelect = null,
        #[Field(of: Modifier::class)]
        public ?array $modifiers = null,
        #[Field(name: 'adjust_to_portion')]
        public ?bool $adjustToPortion = null,
        public ?bool $status = null,
        public ?array $meta = null,
        public ?array $pivot = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }
}
