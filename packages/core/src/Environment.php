<?php

declare(strict_types=1);

namespace Integrify;

use Integrify\Exception\MissingConfiguration;

/**
 * İnteqrasiyaların işlədiyi mühit.
 */
enum Environment: string
{
    case Test = 'test';
    case Prod = 'prod';

    /**
     * Tanınan yazılışlar. `APP_ENV=production` (Laravel-in default-u) əvvəllər
     * `Test`-ə düşürdü — yəni tətbiq sandbox-a sorğu atırdı və heç bir xəbərdarlıq
     * olmurdu.
     */
    private const ALIASES = [
        'prod' => self::Prod,
        'production' => self::Prod,
        'live' => self::Prod,
        'test' => self::Test,
        'testing' => self::Test,
        'sandbox' => self::Test,
        'dev' => self::Test,
        'development' => self::Test,
        'local' => self::Test,
    ];

    /**
     * Sətirdən mühit oxuyur; tanınmayan dəyər üçün `$fallback` qaytarılır.
     *
     * `null` və boş sətir "təyin olunmayıb" deməkdir. Tanınmayan **dolu** dəyər
     * (məs., `prd`) yazılış xətası ola bilər, ona görə `$strict` ilə exception
     * kimi qaldırmaq olar.
     *
     * @throws MissingConfiguration `$strict` və dəyər tanınmırsa.
     */
    public static function parse(?string $value, self $fallback = self::Test, bool $strict = false): self
    {
        $normalised = strtolower(trim($value ?? ''));

        if ($normalised === '') {
            return $fallback;
        }

        $environment = self::ALIASES[$normalised] ?? null;

        if ($environment !== null) {
            return $environment;
        }

        if ($strict) {
            throw new MissingConfiguration(
                'APP_ENV',
                sprintf(
                    'Value "%s" is not a known environment. Expected one of: %s.',
                    $value ?? '',
                    implode(', ', array_keys(self::ALIASES)),
                ),
            );
        }

        return $fallback;
    }

    public function isProduction(): bool
    {
        return $this === self::Prod;
    }
}
