<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * POS istifadəçisi — ofisiant, kassir, menecer.
 */
final readonly class User extends Data
{
    /**
     * @param int|null $id İstifadəçinin IDsi.
     * @param string|null $email Email ünvanı.
     * @param string|null $username POS-da görünən ad.
     * @param string|null $firstName Adı.
     * @param string|null $lastName Soyadı.
     * @param string|null $pin POS PIN kodu.
     * @param string|null $card POS kart nömrəsi.
     * @param string|null $mobileNumber Mobil nömrə.
     * @param int|null $owner `1` brendin sahibidirsə.
     * @param int|null $hide `1` POS seçimində gizlidirsə.
     * @param int|null $salary Əmək haqqı.
     * @param string|null $barcode POS barkodu.
     * @param string|null $tipMessage POS-da göstərilən bəxşiş mesajı.
     * @param bool|null $canReceiveTips Bəxşiş ala bilirmi.
     * @param string|null $loginAt Son giriş vaxtı.
     * @param bool|null $status Hesab aktivdirmi.
     * @param int|null $bonusBalanceId Bonus balansının IDsi.
     * @param array<array-key, mixed>|null $properties Əlavə parametrlər.
     * @param list<string>|null $image Şəkil url-ləri.
     * @param string|null $createdAt Yaradılma vaxtı.
     * @param string|null $updatedAt Son dəyişiklik vaxtı.
     * @param string|null $deletedAt Silinmə vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $email = null,
        public ?string $username = null,
        #[Field(name: 'first_name')]
        public ?string $firstName = null,
        #[Field(name: 'last_name')]
        public ?string $lastName = null,
        public ?string $pin = null,
        public ?string $card = null,
        #[Field(name: 'mobile_number')]
        public ?string $mobileNumber = null,
        public ?int $owner = null,
        public ?int $hide = null,
        public ?int $salary = null,
        public ?string $barcode = null,
        #[Field(name: 'tip_message')]
        public ?string $tipMessage = null,
        #[Field(name: 'can_receive_tips')]
        public ?bool $canReceiveTips = null,
        #[Field(name: 'login_at')]
        public ?string $loginAt = null,
        public ?bool $status = null,
        #[Field(name: 'bonus_balance_id')]
        public ?int $bonusBalanceId = null,
        public ?array $properties = null,
        public ?array $image = null,
        #[Field(name: 'created_at')]
        public ?string $createdAt = null,
        #[Field(name: 'updated_at')]
        public ?string $updatedAt = null,
        #[Field(name: 'deleted_at')]
        public ?string $deletedAt = null,
    ) {
    }
}
