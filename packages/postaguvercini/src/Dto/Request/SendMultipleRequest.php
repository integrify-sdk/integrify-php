<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `sendMessages()` sorğusunun payload-u — hər alıcıya öz mətni.
 */
final readonly class SendMultipleRequest extends Data
{
    /**
     * @param list<SmsMessage> $messages Nömrə–mətn cütləri.
     * @param string|null $sendDate Göndərilmə vaxtı, `YYYYMMDD HH:MM`. `null` — indi.
     * @param string|null $expireDate Etibarlılıq müddəti, `YYYYMMDD HH:MM`.
     * @param string $channel Kanal: `OTP` və ya `BULK`.
     * @param string|null $originator Göndərən adı.
     * @param string $username Hesabın istifadəçi adı.
     * @param string $password Hesabın parolu.
     */
    public function __construct(
        #[Field(name: 'Messages', of: SmsMessage::class)]
        public array $messages,
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
