<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Bir şəklin ölçü variantları.
 */
final readonly class Image extends Data
{
    /**
     * @param string|null $original Orijinal ölçü.
     * @param string|null $large Böyük ölçü.
     * @param string|null $extraLarge Ən böyük ölçü.
     * @param string|null $thumb Kiçik önizləmə.
     * @param string|null $blurHash Yüklənənə qədər göstərilən BlurHash.
     */
    public function __construct(
        public ?string $original = null,
        public ?string $large = null,
        #[Field(name: 'extra_large')]
        public ?string $extraLarge = null,
        public ?string $thumb = null,
        #[Field(name: 'blur_hash')]
        public ?string $blurHash = null,
    ) {
    }
}
