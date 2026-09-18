<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Ödənişi edən brauzer haqqında bankın topladığı məlumat.
 *
 * 3-D Secure risk qiymətləndirməsi üçün toplanır. `*Replaced` field-ləri brauzerin
 * bildirdiyi dəyərin dəyişdirilmiş görünüb-görünmədiyini bildirir — fırıldaq
 * əlaməti ola bilər.
 *
 * Bütün field-lər nullable-dir: bank hansının gələcəyinə zəmanət vermir.
 */
final readonly class ConsumerDeviceBrowser extends Data
{
    /**
     * @param string|null $userAgent Brauzerin `User-Agent` sətri.
     * @param int|null $colorDepth Ekranın rəng dərinliyi.
     * @param float|null $pixelRatio Piksel nisbəti.
     * @param string|null $language Brauzerin dili.
     * @param int|null $tzOffset Saat qurşağının fərqi (dəqiqə).
     * @param bool|null $localStorage `localStorage` mövcuddurmu.
     * @param bool|null $languageReplaced Dil dəyəri dəyişdirilib görünürmü.
     * @param bool|null $resolutionReplaced Ekran ölçüsü dəyişdirilib görünürmü.
     * @param bool|null $osReplaced Əməliyyat sistemi dəyişdirilib görünürmü.
     * @param bool|null $browserReplaced Brauzer dəyişdirilib görünürmü.
     * @param int|null $screenW Ekranın eni.
     * @param int|null $screenH Ekranın hündürlüyü.
     * @param int|null $screenAvailW Əlçatan ekran eni.
     * @param int|null $screenAvailH Əlçatan ekran hündürlüyü.
     * @param string|null $platform Platforma.
     * @param string|null $acceptHeader `Accept` header-i.
     * @param string|null $ip Müştərinin IP ünvanı.
     * @param string|null $refUrl Yönləndirən səhifə.
     * @param bool|null $javaEnabled Java aktivdirmi.
     * @param bool|null $jsEnabled JavaScript aktivdirmi.
     */
    public function __construct(
        #[Field(name: 'userAgent')]
        public ?string $userAgent = null,
        #[Field(name: 'colorDepth')]
        public ?int $colorDepth = null,
        #[Field(name: 'pixelRatio')]
        public ?float $pixelRatio = null,
        public ?string $language = null,
        #[Field(name: 'tzOffset')]
        public ?int $tzOffset = null,
        #[Field(name: 'localStorage')]
        public ?bool $localStorage = null,
        #[Field(name: 'languageReplaced')]
        public ?bool $languageReplaced = null,
        #[Field(name: 'resolutionReplaced')]
        public ?bool $resolutionReplaced = null,
        #[Field(name: 'osReplaced')]
        public ?bool $osReplaced = null,
        #[Field(name: 'browserReplaced')]
        public ?bool $browserReplaced = null,
        #[Field(name: 'screenW')]
        public ?int $screenW = null,
        #[Field(name: 'screenH')]
        public ?int $screenH = null,
        #[Field(name: 'screenAvailW')]
        public ?int $screenAvailW = null,
        #[Field(name: 'screenAvailH')]
        public ?int $screenAvailH = null,
        public ?string $platform = null,
        #[Field(name: 'acceptHeader')]
        public ?string $acceptHeader = null,
        public ?string $ip = null,
        #[Field(name: 'refUrl')]
        public ?string $refUrl = null,
        #[Field(name: 'javaEnabled')]
        public ?bool $javaEnabled = null,
        #[Field(name: 'jsEnabled')]
        public ?bool $jsEnabled = null,
    ) {
    }
}
