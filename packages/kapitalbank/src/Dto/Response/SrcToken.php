<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Sifarişə bağlanmış kart tokeni.
 *
 * `id` sonrakı ödənişlərdə istifadə olunan token nömrəsidir — `payWithSavedCard()`
 * məhz bunu gözləyir.
 */
final readonly class SrcToken extends Data
{
    /**
     * @param int|null $id Token nömrəsi.
     * @param string|null $paymentMethod Ödəniş üsulu.
     * @param string|null $role Tokenin rolu.
     * @param string|null $status Tokenin vəziyyəti.
     * @param string|null $regTime Qeydiyyat vaxtı.
     * @param string|null $entryMode Kartın daxil edilmə üsulu.
     * @param string|null $displayName Kartın göstərilən adı (maskalanmış nömrə).
     * @param CardDetails|null $card Kartın məlumatları.
     */
    public function __construct(
        public ?int $id = null,
        #[Field(name: 'paymentMethod')]
        public ?string $paymentMethod = null,
        public ?string $role = null,
        public ?string $status = null,
        #[Field(name: 'regTime')]
        public ?string $regTime = null,
        #[Field(name: 'entryMode')]
        public ?string $entryMode = null,
        #[Field(name: 'displayName')]
        public ?string $displayName = null,
        public ?CardDetails $card = null,
    ) {
    }
}
