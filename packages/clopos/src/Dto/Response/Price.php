<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Bir qiymət sətri — filiala və ya vaxt intervalına görə.
 *
 * `from` field-i PHP-də açar sözdür, ona görə property `from` yox, `fromInterval`
 * adlanır; məftildəki ad `#[Field]`-dədir.
 */
final readonly class Price extends Data
{
    /**
     * @param string|null $price Qiymət.
     * @param int|null $fromInterval Hansı intervaldan etibarən (`TIMER` məhsullar üçün).
     * @param int|null $venueId Hansı filial üçün.
     */
    public function __construct(
        public ?string $price = null,
        #[Field(name: 'from')]
        public ?int $fromInterval = null,
        #[Field(name: 'venue_id')]
        public ?int $venueId = null,
    ) {
    }
}
