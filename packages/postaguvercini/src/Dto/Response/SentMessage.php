<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Göndərilmiş bir SMS-in nəticəsi.
 */
final readonly class SentMessage extends Data
{
    /**
     * @param string|null $messageId Mesajın IDsi — status sorğusunda bu istifadə olunur.
     * @param string|null $receiver Alıcının nömrəsi.
     * @param int|null $charge Bu mesaj üçün silinən kredit sayı.
     */
    public function __construct(
        #[Field(name: 'MessageId')]
        public ?string $messageId = null,
        #[Field(name: 'Receiver')]
        public ?string $receiver = null,
        #[Field(name: 'Charge')]
        public ?int $charge = null,
    ) {
    }
}
