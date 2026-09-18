<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank;

use Integrify\Environment;
use Integrify\Exception\MissingConfiguration;

/**
 * Kapital Bank merchant hesabının məlumatları.
 *
 * Dəyişməz value object-dir və klientə konstruktorda ötürülür — qlobal state və ya
 * sorğu anında environment oxuması yoxdur. `fromEnvironment()` adlandırılmış
 * konstruktordur, yəni environment yalnız siz istəyəndə oxunur.
 *
 * ```php
 * $config = new KapitalConfig(username: 'merchant', password: 'secret');
 * // və ya
 * $config = KapitalConfig::fromEnvironment();
 * ```
 */
final readonly class KapitalConfig
{
    public const TEST_BASE_URL = 'https://txpgtst.kapitalbank.az';

    public const PROD_BASE_URL = 'https://e-commerce.kapitalbank.az';

    public const DEFAULT_LANGUAGE = 'az';

    /**
     * @param string $username Merchant istifadəçi adı.
     * @param string $password Merchant parolu. Basic auth header-ində gedir.
     * @param Environment $environment Test və ya prod şlüzü.
     * @param string $language Ödəniş səhifəsinin dili (`az`, `en`, `ru`).
     * @param string|null $redirectUrl Ödənişdən sonra müştərinin qayıtdığı URL.
     *     `null` — merchant kabinetindəki dəyər işləyir.
     */
    public function __construct(
        public string $username,
        public string $password,
        public Environment $environment = Environment::Test,
        public string $language = self::DEFAULT_LANGUAGE,
        public ?string $redirectUrl = null,
    ) {
    }

    /**
     * Environment dəyişənlərindən qurur.
     *
     * | Dəyişən | Məcburi |
     * | :--- | :--- |
     * | `KAPITAL_USERNAME` | bəli |
     * | `KAPITAL_PASSWORD` | bəli |
     * | `KAPITAL_ENV` | xeyr (`test`) |
     * | `KAPITAL_INTERFACE_LANG` | xeyr (`az`) |
     * | `KAPITAL_REDIRECT_URL` | xeyr |
     *
     * `KAPITAL_ENV` **strict** oxunur: `prd` kimi tanınmayan dəyər `MissingConfiguration`
     * atır. Python kitabxanası belə halda xəbərdarlıq edib test mühitinə düşür, lakin
     * bir xəbərdarlıq asanlıqla gözdən qaçır və nəticəsi real ödənişlərin test şlüzünə
     * getməsidir — və ya əksi. Səhv yazılış burada sorğudan əvvəl dayandırılır.
     *
     * @throws MissingConfiguration Məcburi dəyişən yoxdursa, və ya `KAPITAL_ENV`
     *     tanınmayan dəyərdirsə.
     */
    public static function fromEnvironment(): self
    {
        $language = self::read('KAPITAL_INTERFACE_LANG', required: false);
        $redirect = self::read('KAPITAL_REDIRECT_URL', required: false);

        return new self(
            username: self::read('KAPITAL_USERNAME'),
            password: self::read('KAPITAL_PASSWORD'),
            environment: Environment::parse(self::read('KAPITAL_ENV', required: false), strict: true),
            language: $language === '' ? self::DEFAULT_LANGUAGE : $language,
            redirectUrl: $redirect === '' ? null : $redirect,
        );
    }

    /**
     * Mühitə uyğun şlüz ünvanı.
     */
    public function baseUrl(): string
    {
        return $this->environment->isProduction() ? self::PROD_BASE_URL : self::TEST_BASE_URL;
    }

    /**
     * HTTP Basic auth header-inin dəyəri.
     *
     * Kapital Bank imza deyil, adi basic auth istifadə edir — yəni parol **hər
     * sorğuda** göndərilir, sadəcə base64 ilə. Base64 şifrələmə deyil: bu sorğular
     * yalnız TLS altında getməlidir, və log-lara `Authorization` header-i düşməməlidir.
     */
    public function authorization(): string
    {
        return 'Basic ' . base64_encode($this->username . ':' . $this->password);
    }

    /**
     * @throws MissingConfiguration
     */
    private static function read(string $variable, bool $required = true): string
    {
        $value = getenv($variable);

        if ($value === false || $value === '') {
            /** @var mixed $fallback */
            $fallback = $_ENV[$variable] ?? $_SERVER[$variable] ?? null;
            $value = is_string($fallback) ? $fallback : '';
        }

        if ($value === '' && $required) {
            throw new MissingConfiguration($variable, 'Set it, or pass the value to the KapitalConfig constructor.');
        }

        return $value;
    }
}
