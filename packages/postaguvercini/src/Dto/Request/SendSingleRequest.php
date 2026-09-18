<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `sendSms()` sorğusunun payload-u — bir mətn, çox alıcı.
 *
 * Bütün field adları məftildə **PascalCase**-dir.
 */
final readonly class SendSingleRequest extends Data
{
    /**
     * @param string $message Göndəriləcək mətn.
     * @param list<string> $receivers Alıcı nömrələri.
     * @param string|null $sendDate Göndərilmə vaxtı, `YYYYMMDD HH:MM`. `null` — indi.
     * @param string|null $expireDate Etibarlılıq müddəti, `YYYYMMDD HH:MM`.
     * @param string $channel Kanal: `OTP` və ya `BULK`.
     * @param string|null $originator Göndərən adı.
     * @param string $username Hesabın istifadəçi adı.
     * @param string $password Hesabın parolu.
     */
    public function __construct(
        #[Field(name: 'Message')]
        public string $message,
        #[Field(name: 'Receivers')]
        public array $receivers,
        #[Field(name: 'SendDate')]
        public ?string $sendDate = null,
        #[Field(name: 'ExpireDate')]
        public ?string $expireDate = null,
        #[Field(name: 'Channel')]
        public string $channel = 'OTP',
        #[Field(name: 'Originator')]
        public ?string $originator = null,
        #[Field(name: 'Username')]
        public string $username = '',
        #[Field(name: 'Password')]
        public string $password = '',
    ) {
    }
}
