<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Clopos\Enum\CategoryType;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Menyu kateqoriyası.
 *
 * Kateqoriyalar ağac qurur: `parentId` valideyni göstərir, `children` isə
 * `includeChildren: true` ilə sorğu atıldıqda dolur.
 *
 * `lft` və `rgt` — nested set sərhədləridir (`_lft` / `_rgt`). Onlar ağacın
 * daxili təmsilidir; adətən `parentId` və `children` kifayət edir.
 */
final readonly class Category extends Data
{
    /**
     * @param int|null $id Kateqoriyanın IDsi.
     * @param string|null $name Adı.
     * @param int|null $status `1` aktiv, `0` deaktiv.
     * @param bool|null $hidden Gizlidirmi.
     * @param string|null $type Kateqoriyanın növü. Enum üçün `type()`.
     * @param Image|null $image Şəkli.
     * @param int|null $position Menyudakı mövqe.
     * @param int|null $emenuPosition eMenu-dakı mövqe.
     * @param array<string, mixed>|null $meta Əlavə parametrlər.
     * @param bool|null $emenuHidden eMenu-da gizlidirmi.
     * @param string|null $code Kateqoriyanın kodu.
     * @param int|null $lft Nested set sol sərhədi (`_lft`).
     * @param int|null $rgt Nested set sağ sərhədi (`_rgt`).
     * @param int|null $depth Ağacdakı dərinlik.
     * @param int|null $parentId Valideyn kateqoriya, kökdə `null`.
     * @param string|null $slug URL-ə uyğun identifikator.
     * @param string|null $color HEX rəng.
     * @param array<array-key, mixed>|null $properties Əlavə parametrlər.
     * @param list<Media>|null $media Media faylları.
     * @param list<Category>|null $children Alt kateqoriyalar.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?int $status = null,
        public ?bool $hidden = null,
        public ?string $type = null,
        public ?Image $image = null,
        public ?int $position = null,
        #[Field(name: 'emenu_position')]
        public ?int $emenuPosition = null,
        public ?array $meta = null,
        #[Field(name: 'emenu_hidden')]
        public ?bool $emenuHidden = null,
        public ?string $code = null,
        #[Field(name: '_lft')]
        public ?int $lft = null,
        #[Field(name: '_rgt')]
        public ?int $rgt = null,
        public ?int $depth = null,
        #[Field(name: 'parent_id')]
        public ?int $parentId = null,
        public ?string $slug = null,
        public ?string $color = null,
        public ?array $properties = null,
        #[Field(of: Media::class)]
        public ?array $media = null,
        #[Field(of: self::class)]
        public ?array $children = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }

    /**
     * `type`-ın enum qarşılığı, tanınmayan dəyər üçün `null`.
     */
    public function type(): ?CategoryType
    {
        return $this->type === null ? null : CategoryType::tryFrom($this->type);
    }
}
