<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Clopos\Enum\Gender;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Müştəri.
 *
 * `group`, `balance` və `cashbackBalance` yalnız sorğuda `with` verildikdə dolur —
 * bax `listCustomers(with: ['group', 'balance'])`.
 *
 * Məbləğlər **sətir** saxlanılır: servis onları JSON ədədi kimi qaytarır, `float`-a
 * çevirmək isə qəpik dəqiqliyini itirərdi.
 */
final readonly class Customer extends Data
{
    /**
     * @param int|null $id Müştərinin IDsi.
     * @param int|null $venueId Aid olduğu filial.
     * @param string|null $cid POS-dakı unikal identifikator.
     * @param int|null $groupId Müştəri qrupunun IDsi.
     * @param CustomerGroup|null $group Qrupun özü (`with[]=group`).
     * @param int|null $balanceId Balansın IDsi.
     * @param string|null $name Adı.
     * @param string|null $discount Şəxsi endirim.
     * @param string|null $email Email ünvanı.
     * @param string|null $phone Əsas telefon nömrəsi.
     * @param list<string>|null $phones Bütün telefon nömrələri.
     * @param string|null $address Əsas ünvan.
     * @param list<string>|null $addresses Saxlanılmış ünvanlar.
     * @param string|null $description Qeyd.
     * @param list<string>|null $addressData Ünvan məlumatları.
     * @param int|null $bonusBalanceId Bonus balansının IDsi.
     * @param Balance|null $balance Mağaza krediti balansı (`with[]=balance`).
     * @param int|null $cashbackBalanceId Keşbek balansının IDsi.
     * @param CashbackBalance|null $cashbackBalance Keşbek balansı (`with[]=cashback_balance`).
     * @param string|null $spent Ümumi xərclənmiş məbləğ.
     * @param string|null $totalDiscount Ümumi endirim.
     * @param string|null $totalBonus Ümumi bonus.
     * @param int|null $receiptCount Çeklərin sayı.
     * @param int|null $gender Cinsi. Enum üçün `gender()`.
     * @param string|null $dateOfBirth Doğum tarixi.
     * @param string|null $code Müştərinin kodu.
     * @param string|null $source Müştərinin mənbəyi.
     * @param string|null $referenceId Xarici sistemdəki identifikator.
     * @param string|null $phoneVerifiedAt Nömrənin təsdiq vaxtı.
     * @param bool|null $status Hesab aktivdirmi.
     * @param bool|null $canUseLoyaltySystem Loyallıq sistemindən istifadə edə bilirmi.
     * @param bool|null $isVerified Təsdiqlənibmi.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        #[Field(name: 'venue_id')]
        public ?int $venueId = null,
        public ?string $cid = null,
        #[Field(name: 'group_id')]
        public ?int $groupId = null,
        public ?CustomerGroup $group = null,
        #[Field(name: 'balance_id')]
        public ?int $balanceId = null,
        public ?string $name = null,
        public ?string $discount = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?array $phones = null,
        public ?string $address = null,
        public ?array $addresses = null,
        public ?string $description = null,
        #[Field(name: 'address_data')]
        public ?array $addressData = null,
        #[Field(name: 'bonus_balance_id')]
        public ?int $bonusBalanceId = null,
        public ?Balance $balance = null,
        #[Field(name: 'cashback_balance_id')]
        public ?int $cashbackBalanceId = null,
        #[Field(name: 'cashback_balance')]
        public ?CashbackBalance $cashbackBalance = null,
        public ?string $spent = null,
        #[Field(name: 'total_discount')]
        public ?string $totalDiscount = null,
        #[Field(name: 'total_bonus')]
        public ?string $totalBonus = null,
        #[Field(name: 'receipt_count')]
        public ?int $receiptCount = null,
        public ?int $gender = null,
        #[Field(name: 'date_of_birth')]
        public ?string $dateOfBirth = null,
        public ?string $code = null,
        public ?string $source = null,
        #[Field(name: 'reference_id')]
        public ?string $referenceId = null,
        #[Field(name: 'phone_verified_at')]
        public ?string $phoneVerifiedAt = null,
        public ?bool $status = null,
        #[Field(name: 'can_use_loyalty_system')]
        public ?bool $canUseLoyaltySystem = null,
        #[Field(name: 'is_verified')]
        public ?bool $isVerified = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }

    /**
     * `gender`-in enum qarşılığı, tanınmayan dəyər üçün `null`.
     */
    public function gender(): ?Gender
    {
        return $this->gender === null ? null : Gender::tryFrom($this->gender);
    }
}
