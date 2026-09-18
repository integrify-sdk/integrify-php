<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Clopos\Enum\OrderStatus;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Sifariş — xarici kanaldan POS-a düşən sənəd.
 *
 * Sifariş çek (`Receipt`) deyil: POS onu qəbul etdikdən sonra `receiptId` dolur və
 * pul hərəkəti artıq çekin üzərində gedir.
 */
final readonly class Order extends Data
{
    /**
     * @param int|null $id Sifarişin IDsi.
     * @param int|null $venueId Filialın IDsi.
     * @param string|null $type Satış növü.
     * @param string|null $integration İnteqrasiya kanalı.
     * @param string|null $integrationUuid İnteqrasiyadakı UUID.
     * @param int|null $integrationId İnteqrasiyanın IDsi.
     * @param string|null $customerRefId Müştərinin xarici identifikatoru.
     * @param string|null $integrationStatus İnteqrasiyanın vəziyyəti.
     * @param string|null $integrationResponse İnteqrasiyanın cavabı.
     * @param int|null $customerId Müştərinin IDsi.
     * @param int|null $receiveUserId Sifarişi qəbul edən istifadəçi.
     * @param int|null $receiveTerminalId Sifarişin qəbul edildiyi terminal.
     * @param string|null $status Sifarişin vəziyyəti. Enum üçün `status()`.
     * @param OrderPayload|null $payload Sifarişin tərkibi.
     * @param int|null $receiptId POS sifarişi qəbul etdikdən sonra bağlanan çek.
     * @param string|null $paymentStatus Ödənişin vəziyyəti.
     * @param string|null $totalAmount Sifarişin ümumi məbləği.
     * @param list<OrderItem>|null $lineItems POS-un hesabladığı sətirlər.
     * @param SaleType|null $saleType Satış növünün özü.
     * @param int|null $paymentMethodId Ödəniş metodunun IDsi.
     * @param PaymentMethod|null $paymentMethod Ödəniş metodunun özü.
     * @param array<string, mixed>|null $properties Əlavə parametrlər.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        #[Field(name: 'venue_id')]
        public ?int $venueId = null,
        public ?string $type = null,
        public ?string $integration = null,
        #[Field(name: 'integration_uuid')]
        public ?string $integrationUuid = null,
        #[Field(name: 'integration_id')]
        public ?int $integrationId = null,
        #[Field(name: 'customer_ref_id')]
        public ?string $customerRefId = null,
        #[Field(name: 'integration_status')]
        public ?string $integrationStatus = null,
        #[Field(name: 'integration_response')]
        public ?string $integrationResponse = null,
        #[Field(name: 'customer_id')]
        public ?int $customerId = null,
        #[Field(name: 'receive_user_id')]
        public ?int $receiveUserId = null,
        #[Field(name: 'receive_terminal_id')]
        public ?int $receiveTerminalId = null,
        public ?string $status = null,
        public ?OrderPayload $payload = null,
        #[Field(name: 'receipt_id')]
        public ?int $receiptId = null,
        #[Field(name: 'payment_status')]
        public ?string $paymentStatus = null,
        #[Field(name: 'total_amount')]
        public ?string $totalAmount = null,
        #[Field(name: 'line_items', of: OrderItem::class)]
        public ?array $lineItems = null,
        #[Field(name: 'sale_type')]
        public ?SaleType $saleType = null,
        #[Field(name: 'payment_method_id')]
        public ?int $paymentMethodId = null,
        #[Field(name: 'payment_method')]
        public ?PaymentMethod $paymentMethod = null,
        public ?array $properties = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }

    /**
     * `status`-un enum qarşılığı, tanınmayan dəyər üçün `null`.
     */
    public function status(): ?OrderStatus
    {
        return $this->status === null ? null : OrderStatus::tryFrom($this->status);
    }
}
