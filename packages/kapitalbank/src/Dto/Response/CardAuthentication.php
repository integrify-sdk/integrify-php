<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Kartın 3-D Secure autentifikasiyasının nəticəsi.
 */
final readonly class CardAuthentication extends Data
{
    /**
     * @param bool|null $needCvv2 CVV2 tələb olunurmu.
     * @param bool|null $needTds 3-D Secure tələb olunurmu.
     * @param string|null $tranId Autentifikasiya tranzaksiyasının IDsi.
     * @param string|null $tdsDsTranId Directory Server tranzaksiya IDsi.
     * @param string|null $timestamp Autentifikasiyanın vaxtı.
     * @param string|null $tdsProtocolVer 3-D Secure protokolunun versiyası.
     * @param string|null $eci Electronic Commerce Indicator.
     * @param string|null $tdsARes Authentication Response.
     */
    public function __construct(
        #[Field(name: 'needCVV2')]
        public ?bool $needCvv2 = null,
        #[Field(name: 'needTds')]
        public ?bool $needTds = null,
        #[Field(name: 'tranId')]
        public ?string $tranId = null,
        #[Field(name: 'tdsDsTranId')]
        public ?string $tdsDsTranId = null,
        public ?string $timestamp = null,
        #[Field(name: 'tdsProtocolVer')]
        public ?string $tdsProtocolVer = null,
        public ?string $eci = null,
        #[Field(name: 'tdsARes')]
        public ?string $tdsARes = null,
    ) {
    }
}
