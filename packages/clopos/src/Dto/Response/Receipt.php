<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Clopos\Enum\DiscountType;
use Integrify\Clopos\Enum\OrderStatus;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Çek — POS-dakı satış sənədi.
 *
 * Çek sifarişdən (`Order`) fərqlidir: sifariş xarici kanaldan gələn tələbdir, çek isə
 * POS-un öz sənədidir və pul hərəkəti onun üzərindədir.
 *
 * > Open API v2-də çek **yaratmaq və silmək** endpoint-i yoxdur. Mövcud çeki
 * > bağlamaq (`closeReceipt()`) və bağlanmış çekin bir neçə field-ini dəyişmək
 * > (`updateClosedReceipt()`) olar.
 *
 * Məbləğlər **sətir** saxlanılır: servis onları JSON ədədi kimi qaytarır, `float`-a
 * çevirmək isə qəpik dəqiqliyini itirərdi.
 *
 * `totalCost` — yeganə camelCase gələn field-dir (`totalCost`), qalanları
 * `snake_case`-dir. Bu servisin özünün uyğunsuzluğudur, burada düzəldilmir.
 */
final readonly class Receipt extends Data
{
    /**
     * @param int|null $id Çekin IDsi.
     * @param int|null $venueId Filialın IDsi.
     * @param string|null $cashShiftCid Kassa növbəsinin UUIDsi.
     * @param string|null $cid Əməliyyatın UUIDsi.
     * @param int|null $userId İstifadəçinin IDsi.
     * @param int|null $openByUserId Çeki açan istifadəçi.
     * @param int|null $closeByUserId Çeki bağlayan istifadəçi.
     * @param int|null $courierId Kuryerin IDsi.
     * @param int|null $sellerId Satıcının IDsi.
     * @param int|null $terminalId Terminalın IDsi.
     * @param string|null $source Çekin mənbəyi.
     * @param int|null $closedTerminalId Çekin bağlandığı terminal.
     * @param int|null $serviceNotificationId Xidmət bildirişinin IDsi.
     * @param int|null $tableId Masanın IDsi.
     * @param int|null $hallId Zalın IDsi.
     * @param int|null $customerId Müştərinin IDsi.
     * @param int|null $saleTypeId Satış növünün IDsi.
     * @param bool|null $isReturns Geri qaytarma çekidirmi.
     * @param int|null $guests Qonaq sayı.
     * @param int|null $status Çekin vəziyyət kodu.
     * @param int|null $localStatus Lokal vəziyyət kodu.
     * @param bool|null $lock Kilidlidirmi.
     * @param int|null $inventoryStatus Anbar vəziyyəti.
     * @param int|null $reportStatus Hesabat vəziyyəti.
     * @param array<string, mixed>|null $meta Əlavə məlumatlar.
     * @param int|null $suspicion Şübhə səviyyəsi.
     * @param bool|null $printed Çap olunubmu.
     * @param string|null $total Yığılan ümumi məbləğ.
     * @param string|null $subtotal Düzəlişlərdən əvvəlki məbləğ.
     * @param string|null $originalSubtotal İlkin məbləğ.
     * @param string|null $giftTotal Hədiyyələrin məbləği.
     * @param string|null $totalCost Ümumi maya dəyəri (`totalCost`).
     * @param list<ReceiptPaymentMethod>|null $paymentMethods Ödəniş sətirləri.
     * @param string|null $fiscalId Fiskal identifikator.
     * @param string|null $byCash Nağd ödənilən məbləğ.
     * @param string|null $byCard Kartla ödənilən məbləğ.
     * @param string|null $remaining Qalan borc.
     * @param int|null $customerDiscountType Müştəri endiriminin növü.
     * @param int|null $discountType Endirimin növü. Enum üçün `discountType()`.
     * @param string|null $discountValue Endirimin dəyəri.
     * @param string|null $discountRate Endirimin dərəcəsi.
     * @param string|null $rpsDiscount RPS endirimi.
     * @param string|null $serviceCharge Xidmət haqqı.
     * @param string|null $serviceChargeValue Xidmət haqqının məbləği.
     * @param string|null $includedTax Daxili vergi (`i_tax`).
     * @param string|null $deliveryFee Çatdırılma haqqı.
     * @param string|null $excludedTax Xarici vergi (`e_tax`).
     * @param string|null $totalTax Ümumi vergi.
     * @param string|null $description Qeyd.
     * @param string|null $address Ünvan.
     * @param string|null $terminalVersion Terminalın versiyası.
     * @param int|null $loyaltyType Loyallıq növü.
     * @param string|null $loyaltyValue Loyallıq dəyəri.
     * @param string|null $orderStatus Sifarişin vəziyyəti. Enum üçün `orderStatus()`.
     * @param string|null $orderNumber Sifariş nömrəsi.
     * @param list<ReceiptProduct>|null $receiptProducts Çekin sətirləri.
     * @param string|null $terminalUpdatedAt Terminaldakı son dəyişiklik vaxtı.
     * @param string|null $closedAt Bağlanma vaxtı.
     * @param string|null $shiftDate Növbənin tarixi.
     * @param int|null $giftCount Hədiyyə sayı.
     * @param string|null $totalDiscount Ümumi endirim.
     * @param array<string, mixed>|null $properties Əlavə parametrlər.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        #[Field(name: 'venue_id')]
        public ?int $venueId = null,
        #[Field(name: 'cash_shift_cid')]
        public ?string $cashShiftCid = null,
        public ?string $cid = null,
        #[Field(name: 'user_id')]
        public ?int $userId = null,
        #[Field(name: 'open_by_user_id')]
        public ?int $openByUserId = null,
        #[Field(name: 'close_by_user_id')]
        public ?int $closeByUserId = null,
        #[Field(name: 'courier_id')]
        public ?int $courierId = null,
        #[Field(name: 'seller_id')]
        public ?int $sellerId = null,
        #[Field(name: 'terminal_id')]
        public ?int $terminalId = null,
        public ?string $source = null,
        #[Field(name: 'closed_terminal_id')]
        public ?int $closedTerminalId = null,
        #[Field(name: 'service_notification_id')]
        public ?int $serviceNotificationId = null,
        #[Field(name: 'table_id')]
        public ?int $tableId = null,
        #[Field(name: 'hall_id')]
        public ?int $hallId = null,
        #[Field(name: 'customer_id')]
        public ?int $customerId = null,
        #[Field(name: 'sale_type_id')]
        public ?int $saleTypeId = null,
        #[Field(name: 'is_returns')]
        public ?bool $isReturns = null,
        public ?int $guests = null,
        public ?int $status = null,
        #[Field(name: 'local_status')]
        public ?int $localStatus = null,
        public ?bool $lock = null,
        #[Field(name: 'inventory_status')]
        public ?int $inventoryStatus = null,
        #[Field(name: 'report_status')]
        public ?int $reportStatus = null,
        public ?array $meta = null,
        public ?int $suspicion = null,
        public ?bool $printed = null,
        public ?string $total = null,
        public ?string $subtotal = null,
        #[Field(name: 'original_subtotal')]
        public ?string $originalSubtotal = null,
        #[Field(name: 'gift_total')]
        public ?string $giftTotal = null,
        #[Field(name: 'totalCost')]
        public ?string $totalCost = null,
        #[Field(name: 'payment_methods', of: ReceiptPaymentMethod::class)]
        public ?array $paymentMethods = null,
        #[Field(name: 'fiscal_id')]
        public ?string $fiscalId = null,
        #[Field(name: 'by_cash')]
        public ?string $byCash = null,
        #[Field(name: 'by_card')]
        public ?string $byCard = null,
        public ?string $remaining = null,
        #[Field(name: 'customer_discount_type')]
        public ?int $customerDiscountType = null,
        #[Field(name: 'discount_type')]
        public ?int $discountType = null,
        #[Field(name: 'discount_value')]
        public ?string $discountValue = null,
        #[Field(name: 'discount_rate')]
        public ?string $discountRate = null,
        #[Field(name: 'rps_discount')]
        public ?string $rpsDiscount = null,
        #[Field(name: 'service_charge')]
        public ?string $serviceCharge = null,
        #[Field(name: 'service_charge_value')]
        public ?string $serviceChargeValue = null,
        #[Field(name: 'i_tax')]
        public ?string $includedTax = null,
        #[Field(name: 'delivery_fee')]
        public ?string $deliveryFee = null,
        #[Field(name: 'e_tax')]
        public ?string $excludedTax = null,
        #[Field(name: 'total_tax')]
        public ?string $totalTax = null,
        public ?string $description = null,
        public ?string $address = null,
        #[Field(name: 'terminal_version')]
        public ?string $terminalVersion = null,
        #[Field(name: 'loyalty_type')]
        public ?int $loyaltyType = null,
        #[Field(name: 'loyalty_value')]
        public ?string $loyaltyValue = null,
        #[Field(name: 'order_status')]
        public ?string $orderStatus = null,
        #[Field(name: 'order_number')]
        public ?string $orderNumber = null,
        #[Field(name: 'receipt_products', of: ReceiptProduct::class)]
        public ?array $receiptProducts = null,
        #[Field(name: 'terminal_updated_at')]
        public ?string $terminalUpdatedAt = null,
        #[Field(name: 'closed_at')]
        public ?string $closedAt = null,
        #[Field(name: 'shift_date')]
        public ?string $shiftDate = null,
        #[Field(name: 'gift_count')]
        public ?int $giftCount = null,
        #[Field(name: 'total_discount')]
        public ?string $totalDiscount = null,
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
     * `discountType`-ın enum qarşılığı, tanınmayan dəyər üçün `null`.
     */
    public function discountType(): ?DiscountType
    {
        return $this->discountType === null ? null : DiscountType::tryFrom($this->discountType);
    }

    /**
     * `orderStatus`-un enum qarşılığı, tanınmayan dəyər üçün `null`.
     */
    public function orderStatus(): ?OrderStatus
    {
        return $this->orderStatus === null ? null : OrderStatus::tryFrom($this->orderStatus);
    }
}
