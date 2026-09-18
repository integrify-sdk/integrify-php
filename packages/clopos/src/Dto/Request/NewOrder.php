<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `createOrder()`-ə verilən sifariş tərkibi.
 *
 * ```php
 * $order = new NewOrder(
 *     service: new NewOrderService(2, 'Delivery', 1, 'Main'),
 *     customer: new NewOrderCustomer(9, 'Rəşid Axundzadə', phone: '+994705401040'),
 *     products: [new NewOrderProduct(productId: 1, count: 1)],
 * );
 *
 * $client->createOrder(customerId: 1, order: $order);
 * ```
 */
final readonly class NewOrder extends Data
{
    /**
     * @param NewOrderService $service Satış kanalı.
     * @param NewOrderCustomer $customer Müştəri.
     * @param list<NewOrderProduct> $products Sifarişdəki məhsullar.
     * @param array<string, mixed>|null $meta Sifarişin əlavə məlumatları.
     */
    public function __construct(
        public NewOrderService $service,
        public NewOrderCustomer $customer,
        #[Field(of: NewOrderProduct::class)]
        public array $products,
        public ?array $meta = null,
    ) {
    }

    /**
     * Payload forması.
     *
     * `customer` ayrıca qurulur, çünki onun iki field-i `null` olaraq qalmalıdır —
     * bax [`NewOrderCustomer`](NewOrderCustomer.php).
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = $this->toArray(skipNull: true);
        $payload['customer'] = $this->customer->toPayload();

        return $payload;
    }
}
