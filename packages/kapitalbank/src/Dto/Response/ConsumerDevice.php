<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Data;

/**
 * Ödənişi edən cihaz.
 */
final readonly class ConsumerDevice extends Data
{
    /**
     * @param ConsumerDeviceBrowser|null $browser Brauzerin məlumatları.
     */
    public function __construct(
        public ?ConsumerDeviceBrowser $browser = null,
    ) {
    }
}
