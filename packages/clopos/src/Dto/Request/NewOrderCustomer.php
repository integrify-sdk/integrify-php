<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Yeni sifarişin müştərisi.
 *
 * > **`phone` və `customerDiscountType` verilməsə də məftildə görünür — `null` olaraq.**
 * > Python modelində bu iki field-in default dəyəri `UNSET` deyil, `None`-dur, ona görə
 * > pydantic onları payload-dan çıxarmır. `address` isə `UNSET` default-lu olduğu üçün
 * > tamamilə düşür. Clopos sifariş payload-unu JSON kimi saxlayıb geri qaytardığı üçün
 * > "yoxdur" ilə "null-dur" fərqi görünə bilər — ona görə bu asimmetriya burada
 * > qəsdən təkrarlanır.
 */
final readonly class NewOrderCustomer extends Data
{
    /**
     * @param int $id Müştərinin IDsi.
     * @param string $name Adı.
     * @param string|null $phone Telefon nömrəsi. Verilməsə `null` kimi gedir.
     * @param int|null $customerDiscountType Endirim növü. Verilməsə `null` kimi gedir.
     * @param string|null $address Ünvan. Verilməsə payload-dan tamamilə düşür.
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $phone = null,
        #[Field(name: 'customer_discount_type')]
        public ?int $customerDiscountType = null,
        public ?string $address = null,
    ) {
    }

    /**
     * Payload forması — `null` qalan iki field-i saxlayaraq.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = $this->toArray(skipNull: true);

        // Python modelinin default-ları: bu ikisi `None`-dur, `UNSET` deyil.
        $payload['customer_discount_type'] ??= null;
        $payload['phone'] ??= null;

        return $payload;
    }
}
