<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Clopos\Enum\DiscountType;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Sifarişin içindəki müştəri — tam `Customer` deyil, sifarişlə birlikdə saxlanılan
 * anlıq surət.
 */
final readonly class OrderCustomer extends Data
{
    /**
     * @param int|null $id Müştərinin IDsi.
     * @param string|null $name Adı.
     * @param string|null $phone Telefon nömrəsi.
     * @param string|null $address Ünvan.
     * @param int|null $customerDiscountType Endirim növü. Enum üçün `customerDiscountType()`.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $address = null,
        #[Field(name: 'customer_discount_type')]
        public ?int $customerDiscountType = null,
    ) {
    }

    /**
     * `customerDiscountType`-ın enum qarşılığı, tanınmayan dəyər üçün `null`.
     */
    public function customerDiscountType(): ?DiscountType
    {
        return $this->customerDiscountType === null
            ? null
            : DiscountType::tryFrom($this->customerDiscountType);
    }
}
