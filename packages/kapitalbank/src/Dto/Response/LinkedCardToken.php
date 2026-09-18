<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `linkCardToken()` cavabı — saxlanılmış kartın sifarişə bağlanması.
 */
final readonly class LinkedCardToken extends Data
{
    /**
     * @param string|null $status Bağlanmanın nəticəsi.
     * @param string|null $cvv2AuthStatus CVV2 yoxlamasının nəticəsi.
     * @param string|null $tdsV1AuthStatus 3-D Secure v1 nəticəsi.
     * @param string|null $tdsV2AuthStatus 3-D Secure v2 nəticəsi.
     * @param string|null $otpAutStatus OTP yoxlamasının nəticəsi.
     * @param SrcToken|null $srcToken Bağlanmış token.
     */
    public function __construct(
        public ?string $status = null,
        #[Field(name: 'cvv2AuthStatus')]
        public ?string $cvv2AuthStatus = null,
        #[Field(name: 'tdsV1AuthStatus')]
        public ?string $tdsV1AuthStatus = null,
        #[Field(name: 'tdsV2AuthStatus')]
        public ?string $tdsV2AuthStatus = null,
        #[Field(name: 'otpAutStatus')]
        public ?string $otpAutStatus = null,
        #[Field(name: 'srcToken')]
        public ?SrcToken $srcToken = null,
    ) {
    }
}
