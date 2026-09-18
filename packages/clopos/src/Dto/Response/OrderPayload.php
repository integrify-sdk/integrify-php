<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Sifarişin payload-u — POS-a göndərilən tərkib.
 *
 * Bu, `createOrder()`-ə verilən `NewOrder` DTO-sunun servisdən qayıdan formasıdır:
 * eyni struktur, lakin bütün field-lər opsional, çünki POS onları mərhələ-mərhələ
 * doldurur.
 */
final readonly class OrderPayload extends Data
{
    /**
     * @param SaleService|null $service Satış kanalı.
     * @param OrderCustomer|null $customer Müştəri.
     * @param list<OrderProduct>|null $products Sifarişdəki məhsullar.
     * @param array<string, mixed>|null $meta Sifarişin əlavə məlumatları.
     * @param int|null $customerId Müştərinin IDsi.
     * @param int|null $saleTypeId Satış növünün IDsi.
     * @param string|null $payloadUpdatedAt Payload-un son dəyişiklik vaxtı.
     */
    public function __construct(
        public ?SaleService $service = null,
        public ?OrderCustomer $customer = null,
        #[Field(of: OrderProduct::class)]
        public ?array $products = null,
        public ?array $meta = null,
        #[Field(name: 'customer_id')]
        public ?int $customerId = null,
        #[Field(name: 'sale_type_id')]
        public ?int $saleTypeId = null,
        #[Field(name: 'payload_updated_at')]
        public ?string $payloadUpdatedAt = null,
    ) {
    }
}
