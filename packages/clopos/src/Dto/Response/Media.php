<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Obyektə bağlanmış media faylı.
 */
final readonly class Media extends Data
{
    /**
     * @param string|null $uuid Faylın UUIDsi.
     * @param string|null $mimeType MIME tipi.
     * @param int|null $size Ölçü (bayt).
     * @param Image|null $urls Şəklin ölçü variantları.
     * @param string|null $blurHash BlurHash.
     * @param array<string, mixed>|null $dimensions Ölçülər (sərbəst struktur).
     */
    public function __construct(
        public ?string $uuid = null,
        #[Field(name: 'mime_type')]
        public ?string $mimeType = null,
        public ?int $size = null,
        public ?Image $urls = null,
        #[Field(name: 'blur_hash')]
        public ?string $blurHash = null,
        public ?array $dimensions = null,
    ) {
    }
}
