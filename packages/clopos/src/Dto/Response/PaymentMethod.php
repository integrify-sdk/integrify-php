<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Ödəniş metodu — nağd, kart, bonus və s.
 *
 * `status` filial başına aktivlikdir: `{"1": 1, "2": 0}` — yəni birinci filialda
 * aktiv, ikincidə yox.
 */
final readonly class PaymentMethod extends Data
{
    /**
     * @param int|null $id Metodun IDsi.
     * @param string|null $name Metodun adı.
     * @param int|null $customerRequired Müştəri məcburidirmi (`1` / `0`).
     * @param int|null $isSystem Sistem metodudurmu.
     * @param int|null $balanceId Bağlı balansın IDsi.
     * @param Balance|null $balance Bağlı balansın özü.
     * @param array<string, mixed>|null $service Xarici servis inteqrasiyası.
     * @param int|null $split Bölünmüş ödənişdə istifadə oluna bilirmi.
     * @param array<array-key, int>|null $status Filial IDsi → `0`/`1` aktivlik.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        #[Field(name: 'customer_required')]
        public ?int $customerRequired = null,
        #[Field(name: 'is_system')]
        public ?int $isSystem = null,
        #[Field(name: 'balance_id')]
        public ?int $balanceId = null,
        public ?Balance $balance = null,
        public ?array $service = null,
        public ?int $split = null,
        public ?array $status = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }
}
