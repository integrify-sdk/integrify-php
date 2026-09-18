<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Satış növü — "Zalda", "Çatdırılma", "Özün götür" və s.
 *
 * `status` filial başına aktivlikdir: `{"1": 1, "2": 0}`.
 */
final readonly class SaleType extends Data
{
    /**
     * @param int|null $id Satış növünün IDsi.
     * @param string|null $name Adı.
     * @param string|null $systemType Sistem növü, məs. `IN`, `DELIVERY`.
     * @param array<array-key, int>|null $status Filial IDsi → `0`/`1` aktivlik.
     * @param string|null $channel Aid olduğu kanal.
     * @param string|null $serviceChargeRate Xidmət haqqının dərəcəsi.
     * @param int|null $paymentMethodId Default ödəniş metodunun IDsi.
     * @param int|null $position Siyahıdakı mövqe.
     * @param PaymentMethod|null $paymentMethod Default ödəniş metodunun özü.
     * @param list<Media>|null $media Media faylları.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        #[Field(name: 'system_type')]
        public ?string $systemType = null,
        public ?array $status = null,
        public ?string $channel = null,
        #[Field(name: 'service_charge_rate')]
        public ?string $serviceChargeRate = null,
        #[Field(name: 'payment_method_id')]
        public ?int $paymentMethodId = null,
        public ?int $position = null,
        #[Field(name: 'payment_method')]
        public ?PaymentMethod $paymentMethod = null,
        #[Field(of: Media::class)]
        public ?array $media = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }
}
