<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Stansiya — mətbəx, bar və ya digər hazırlıq nöqtəsi.
 *
 * Məhsul stansiyaya bağlanır; sifariş verildikdə çek həmin stansiyanın printerinə
 * düşür.
 */
final readonly class Station extends Data
{
    /**
     * @param int|null $id Stansiyanın IDsi.
     * @param string|null $name Stansiyanın adı.
     * @param int|null $status `1` aktiv, `0` deaktiv.
     * @param int|null $type Stansiyanın növü.
     * @param int|null $printable Çap oluna bilirmi.
     * @param bool|null $canPrint Printerə yönləndirilə bilirmi.
     * @param bool|null $reminderEnabled Xatırlatma bildirişi aktivdirmi.
     * @param array<string, mixed>|null $meta Əlavə parametrlər.
     * @param string|null $description Təsvir.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?int $status = null,
        public ?int $type = null,
        public ?int $printable = null,
        #[Field(name: 'can_print')]
        public ?bool $canPrint = null,
        #[Field(name: 'reminder_enabled')]
        public ?bool $reminderEnabled = null,
        public ?array $meta = null,
        public ?string $description = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }
}
