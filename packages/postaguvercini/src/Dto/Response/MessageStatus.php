<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Bir mesajın çatdırılma vəziyyəti.
 *
 * Diqqət: `isFinalStatus` və `smsCharge` servisdə **sətir** kimi gəlir (`"1"`,
 * `"true"` və s.), bool/int kimi yox — Python sxemi də onları `str` saxlayır.
 * `isFinal()` həmin sətri oxuyur.
 */
final readonly class MessageStatus extends Data
{
    /**
     * @param string|null $messageId Mesajın IDsi.
     * @param string|null $receiver Alıcının nömrəsi.
     * @param string|null $smsStatus Vəziyyətin kodu.
     * @param string|null $smsStatusDescription Vəziyyətin izahı.
     * @param string|null $isFinalStatus Vəziyyət yekundurmu — **sətir** kimi gəlir.
     * @param string|null $statusTime Vəziyyətin qeyd olunma vaxtı.
     * @param string|null $smsCharge Silinən kredit sayı — **sətir** kimi gəlir.
     */
    public function __construct(
        #[Field(name: 'MessageId')]
        public ?string $messageId = null,
        #[Field(name: 'Receiver')]
        public ?string $receiver = null,
        #[Field(name: 'SmsStatus')]
        public ?string $smsStatus = null,
        #[Field(name: 'SmsStatusDescription')]
        public ?string $smsStatusDescription = null,
        #[Field(name: 'IsFinalStatus')]
        public ?string $isFinalStatus = null,
        #[Field(name: 'StatusTime')]
        public ?string $statusTime = null,
        #[Field(name: 'SmsCharge')]
        public ?string $smsCharge = null,
    ) {
    }

    /**
     * Vəziyyət yekundurmu.
     *
     * Servis bunu sətir kimi qaytarır və formatına zəmanət vermir, ona görə həm
     * `"1"`, həm `"true"`, həm də `"True"` qəbul olunur. Tanınmayan dəyər `false`
     * sayılır — yəni "hələ yekun deyil", bu da yenidən soruşmağa aparır, səhvən
     * "bitdi" deməkdən təhlükəsizdir.
     */
    public function isFinal(): bool
    {
        return in_array(strtolower(trim($this->isFinalStatus ?? '')), ['1', 'true', 'yes'], true);
    }
}
