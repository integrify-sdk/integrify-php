<?php

declare(strict_types=1);

namespace Integrify\EPoint;

use Integrify\Exception\MissingConfiguration;

/**
 * EPoint hesabının açarları və sorğu default-ları.
 *
 * Dəyişməz value object-dir və klientə konstruktorda ötürülür — qlobal state və ya
 * sorğu anında environment oxuması yoxdur. `fromEnvironment()` adlandırılmış
 * konstruktordur, yəni environment yalnız siz istəyəndə oxunur.
 *
 * ```php
 * $config = new EPointConfig(publicKey: 'i000000001', privateKey: 'secret');
 * // və ya
 * $config = EPointConfig::fromEnvironment();
 * ```
 */
final readonly class EPointConfig
{
    /** EPoint-in default interfeys dili. */
    public const DEFAULT_LANGUAGE = 'az';

    /**
     * @param string $publicKey EPoint-in verdiyi public key. Hər payload-a düşür.
     * @param string $privateKey EPoint-in verdiyi private key. **Heç vaxt payload-a
     *     düşmür** — yalnız `signature()` hesablamasında istifadə olunur.
     * @param string $language Ödəniş səhifəsinin dili (`az`, `en`, `ru`).
     * @param string|null $successRedirectUrl Uğurlu ödənişdən sonra müştərinin
     *     yönləndirildiyi URL. `null` — EPoint dashboard-ındakı dəyər işləyir.
     * @param string|null $errorRedirectUrl Uğursuz ödənişdən sonra yönləndirilən URL.
     */
    public function __construct(
        public string $publicKey,
        public string $privateKey,
        public string $language = self::DEFAULT_LANGUAGE,
        public ?string $successRedirectUrl = null,
        public ?string $errorRedirectUrl = null,
    ) {
    }

    /**
     * Environment dəyişənlərindən qurur.
     *
     * | Dəyişən | Məcburi |
     * | :--- | :--- |
     * | `EPOINT_PUBLIC_KEY` | bəli |
     * | `EPOINT_PRIVATE_KEY` | bəli |
     * | `EPOINT_INTERFACE_LANG` | xeyr (`az`) |
     * | `EPOINT_SUCCESS_REDIRECT_URL` | xeyr |
     * | `EPOINT_FAILED_REDIRECT_URL` | xeyr |
     *
     * Diqqət: environment dəyişəni `EPOINT_FAILED_REDIRECT_URL` adlanır, API-də
     * gedən field isə `error_redirect_url`. Adlar Python kitabxanası ilə eyni
     * saxlanılıb ki, iki SDK bir `.env` faylını paylaşa bilsin.
     *
     * @throws MissingConfiguration Məcburi dəyişən yoxdursa.
     */
    public static function fromEnvironment(): self
    {
        $language = self::read('EPOINT_INTERFACE_LANG', required: false);
        $success = self::read('EPOINT_SUCCESS_REDIRECT_URL', required: false);
        $error = self::read('EPOINT_FAILED_REDIRECT_URL', required: false);

        return new self(
            publicKey: self::read('EPOINT_PUBLIC_KEY'),
            privateKey: self::read('EPOINT_PRIVATE_KEY'),
            language: $language === '' ? self::DEFAULT_LANGUAGE : $language,
            successRedirectUrl: $success === '' ? null : $success,
            errorRedirectUrl: $error === '' ? null : $error,
        );
    }

    /**
     * Payload imzası.
     *
     * EPoint-in gözlədiyi düstur: `base64(sha1(private_key + data + private_key))`,
     * burada `data` göndərilən base64 payload-ın **özüdür**, onun decode olunmuş
     * hali deyil.
     *
     * `sha1()`-ın ikinci arqumenti `true`-dur: EPoint hash-in **xam baytlarını**
     * base64-ləyir. Hex sətri base64-ləmək (`base64_encode(sha1($s))`) 40 simvollu,
     * tamam başqa bir imza verir və EPoint onu `Signature did not match` ilə rədd edir.
     *
     * @param string $data Base64 formatında payload.
     */
    public function signature(string $data): string
    {
        return base64_encode(sha1($this->privateKey . $data . $this->privateKey, true));
    }

    /**
     * Gələn imzanın gözlənilənlə eyniliyini **sabit zamanda** yoxlayır.
     *
     * Adi `===` müqayisəsi ilk fərqli baytda dayanır, yəni müqayisənin davam etdiyi
     * müddət düzgün prefiksin uzunluğunu açır. Callback imzası hücumçunun nəzarətində
     * olan dəyərdir, ona görə müqayisə `hash_equals()` ilə aparılır.
     */
    public function verify(string $data, string $signature): bool
    {
        return hash_equals($this->signature($data), $signature);
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
            throw new MissingConfiguration($variable, 'Set it, or pass the value to the EPointConfig constructor.');
        }

        return $value;
    }
}
