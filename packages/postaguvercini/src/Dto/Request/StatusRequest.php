<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `getStatus()` sorğusunun payload-u.
 *
 * Göndərmə sorğularından fərqli olaraq burada `SendDate`/`ExpireDate`/`Channel`
 * field-ləri **ümumiyyətlə yoxdur** — Python sxemi də onları elan etmir.
 */
final readonly class StatusRequest extends Data
{
    /**
     * @param list<string> $messageIds Vəziyyəti soruşulan mesaj IDləri.
     * @param string $username Hesabın istifadəçi adı.
     * @param string $password Hesabın parolu.
     */
    public function __construct(
        #[Field(name: 'MessageIds')]
        public array $messageIds,
        #[Field(name: 'Username')]
        public string $username = '',
        #[Field(name: 'Password')]
        public string $password = '',
    ) {
    }
}
