<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `Timer` məhsulun qiymət cədvəli — vaxta görə satılan xidmətlər üçün.
 */
final readonly class TimerSetting extends Data
{
    /**
     * @param int|null $interval Qiymət intervalı (dəqiqə).
     * @param list<Price>|null $prices İntervallara görə qiymətlər.
     */
    public function __construct(
        public ?int $interval = null,
        #[Field(of: Price::class)]
        public ?array $prices = null,
    ) {
    }
}
