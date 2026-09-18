<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\Kapitalbank\Enum\OrderStatus;

/**
 * `getOrderInformation()` cavabı — sifarişin qısa vəziyyəti.
 *
 * Tam məlumat üçün `getDetailedOrderInformation()`.
 */
final readonly class OrderInformation extends Data
{
    /**
     * @param int $id Sifariş IDsi.
     * @param string $typeRid Sifariş növünün identifikatoru.
     * @param string $status Sifarişin vəziyyəti. Enum üçün `status()`.
     * @param string $lastStatusLogin Vəziyyəti sonuncu dəyişən istifadəçi.
     * @param string $amount Sifarişin məbləği. **Sətir** saxlanılır: bank onu JSON
     *     ədədi kimi qaytarır, `float`-a çevirmək isə qəpik dəqiqliyini itirə bilər.
     * @param string $currency Məzənnə.
     * @param string $createTime Yaradılma vaxtı.
     * @param OrderType|null $type Sifariş növü.
     */
    public function __construct(
        public int $id,
        #[Field(name: 'typeRid')]
        public string $typeRid,
        public string $status,
        #[Field(name: 'lastStatusLogin')]
        public string $lastStatusLogin,
        public string $amount,
        public string $currency,
        #[Field(name: 'createTime')]
        public string $createTime,
        public ?OrderType $type = null,
    ) {
    }

    /** `status`-un enum qarşılığı, tanınırsa. */
    public function status(): ?OrderStatus
    {
        return OrderStatus::tryFrom($this->status);
    }

    /** Sifariş tam ödənilibmi. */
    public function isFullyPaid(): bool
    {
        return $this->status === OrderStatus::FullyPaid->value;
    }
}
