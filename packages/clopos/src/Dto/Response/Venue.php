<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Data;

/**
 * Filial (branch) — brendin bir satış nöqtəsi.
 *
 * Sorğunu konkret filiala yönəltmək üçün `CloposConfig::$venueId` (yəni `x-venue`
 * header-i) istifadə olunur.
 */
final readonly class Venue extends Data
{
    /**
     * @param int|null $id Filialın IDsi.
     * @param string|null $name Filialın adı.
     * @param string|null $address Ünvan.
     * @param int|null $status `1` aktiv, `0` deaktiv.
     * @param string|null $phone Əlaqə nömrəsi.
     * @param string|null $email Əlaqə email-i.
     * @param list<string>|null $media Media url-ləri.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $address = null,
        public ?int $status = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?array $media = null,
    ) {
    }
}
