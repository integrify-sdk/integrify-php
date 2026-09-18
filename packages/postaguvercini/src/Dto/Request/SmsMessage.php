<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `sendMessages()` üçün bir nömrə–mətn cütü.
 *
 * `Send_N_N` endpoint-i hər alıcıya **öz** mətnini göndərir, ona görə nömrə və mətn
 * bir yerdə saxlanılır. Bu, iki ayrı siyahını `zip` etməkdən daha təhlükəsizdir:
 * uzunluqları fərqli olan iki siyahı səssizcə qısaldılır və bir mesaj heç kimə
 * getmir.
 *
 * Məftildə field adları **PascalCase**-dir (`Receiver`, `Message`) — bütün API belədir.
 */
final readonly class SmsMessage extends Data
{
    /**
     * @param string $receiver Nömrə: ölkə kodu + operator kodu + nömrə (`994501234567`).
     * @param string $message Həmin nömrəyə gedən mətn.
     */
    public function __construct(
        #[Field(name: 'Receiver')]
        public string $receiver,
        #[Field(name: 'Message')]
        public string $message,
    ) {
    }
}
