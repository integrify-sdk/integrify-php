<?php

declare(strict_types=1);

namespace Integrify\Clopos;

use Integrify\Exception\MissingConfiguration;
use SensitiveParameter;

/**
 * Clopos klientinin konfiqurasiyası.
 *
 * Dörd dəyər token almaq üçündür (`clientId`, `clientSecret`, `brand`,
 * `integratorId`); `venueId` isə opsionaldır və **hər sorğuda** `x-venue` header-i
 * kimi gedir.
 *
 * > Token-in özü burada saxlanılmır. O, `AzericardConfig`-dəki açar kimi sabit deyil —
 * > bir saat yaşayır və `authenticate()` ilə alınır, ona görə klientin dəyişən
 * > vəziyyətidir, konfiqurasiya deyil.
 */
final readonly class CloposConfig
{
    public const BASE_URL = 'https://integrations.clopos.com/open-api/v2/';

    /**
     * @param string|null $clientId Clopos-un verdiyi client ID. Yalnız `authenticate()` üçün.
     * @param string|null $clientSecret Clopos-un verdiyi client secret. Yalnız `authenticate()` üçün.
     * @param string|null $brand Brend adı — token məhz bu brendə verilir.
     * @param string|null $integratorId Clopos-un verdiyi inteqrator IDsi.
     * @param string|null $venueId Filialın IDsi. Verilsə, hər sorğuya `x-venue` header-i əlavə olunur
     *     və JWT-nin içindəki filialı **həmin sorğu üçün** əvəz edir.
     * @param string $baseUrl API-nin baza url-i. Test mühiti üçün dəyişdirilə bilər.
     */
    public function __construct(
        #[SensitiveParameter]
        public ?string $clientId = null,
        #[SensitiveParameter]
        public ?string $clientSecret = null,
        public ?string $brand = null,
        public ?string $integratorId = null,
        public ?string $venueId = null,
        public string $baseUrl = self::BASE_URL,
    ) {
    }

    /**
     * Mühit dəyişənlərindən qurur.
     *
     * Bu **adlandırılmış konstruktordur**, ambient oxu deyil: dəyərlər bir dəfə, obyekt
     * yaradılarkən oxunur. Heç bir dəyişən məcburi deyil — `authenticate()` çağırılmasa
     * (məs. token xaricdən gəlirsə) client ID və secret lazım deyil.
     *
     * | Dəyişən | Təyinat |
     * | :--- | :--- |
     * | `CLOPOS_CLIENT_ID` | `clientId` |
     * | `CLOPOS_CLIENT_SECRET` | `clientSecret` |
     * | `CLOPOS_BRAND` | `brand` |
     * | `CLOPOS_INTEGRATOR_ID` | `integratorId` |
     * | `CLOPOS_VENUE_ID` | `venueId` |
     */
    public static function fromEnvironment(): self
    {
        return new self(
            clientId: self::env('CLOPOS_CLIENT_ID'),
            clientSecret: self::env('CLOPOS_CLIENT_SECRET'),
            brand: self::env('CLOPOS_BRAND'),
            integratorId: self::env('CLOPOS_INTEGRATOR_ID'),
            venueId: self::env('CLOPOS_VENUE_ID'),
        );
    }

    /**
     * `authenticate()` üçün lazım olan dörd dəyər.
     *
     * @return array{client_id: string, client_secret: string, brand: string, integrator_id: string}
     *
     * @throws MissingConfiguration Hər hansı biri yoxdursa.
     */
    public function credentials(): array
    {
        return [
            'client_id' => $this->require($this->clientId, 'CLOPOS_CLIENT_ID'),
            'client_secret' => $this->require($this->clientSecret, 'CLOPOS_CLIENT_SECRET'),
            'brand' => $this->require($this->brand, 'CLOPOS_BRAND'),
            'integrator_id' => $this->require($this->integratorId, 'CLOPOS_INTEGRATOR_ID'),
        ];
    }

    /**
     * @throws MissingConfiguration
     */
    private function require(?string $value, string $variable): string
    {
        if ($value === null || $value === '') {
            throw new MissingConfiguration(sprintf(
                'Clopos authentication needs %s. Pass it to %s, or set the environment variable.',
                $variable,
                self::class,
            ));
        }

        return $value;
    }

    private static function env(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
