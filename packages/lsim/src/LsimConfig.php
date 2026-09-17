<?php

declare(strict_types=1);

namespace Integrify\Lsim;

use Integrify\Exception\MissingConfiguration;

/**
 * LSIM hesabının məlumatları.
 *
 * Dəyişməz value object-dir və klientə konstruktorda ötürülür — qlobal state və ya
 * sorğu anında environment oxuması yoxdur. `fromEnvironment()` adlandırılmış
 * konstruktordur, yəni environment yalnız siz istəyəndə oxunur.
 *
 * ```php
 * $config = new LsimConfig(login: 'my-login', password: 'secret', senderName: 'MyShop');
 * // və ya
 * $config = LsimConfig::fromEnvironment();
 * ```
 */
final readonly class LsimConfig
{
    /**
     * @param string $login LSIM logini.
     * @param string $password LSIM parolu. Heç vaxt payload-a düşmür — yalnız
     *     `key` hash-ının hesablanmasında istifadə olunur.
     * @param string $senderName LSIM tərəfindən təyin olunmuş göndərən adı.
     */
    public function __construct(
        public string $login,
        public string $password,
        public string $senderName = '',
    ) {
    }

    /**
     * `LSIM_LOGIN`, `LSIM_PASSWORD` və `LSIM_SENDER_NAME` dəyişənlərindən qurur.
     *
     * @throws MissingConfiguration Məcburi dəyişən yoxdursa.
     */
    public static function fromEnvironment(): self
    {
        return new self(
            login: self::read('LSIM_LOGIN'),
            password: self::read('LSIM_PASSWORD'),
            senderName: self::read('LSIM_SENDER_NAME', required: false),
        );
    }

    /**
     * Tək SMS sorğuları üçün imza.
     *
     * LSIM-in gözlədiyi düstur: `md5(md5(password) + login + text + msisdn + sender)`.
     * Parol özü heç vaxt göndərilmir.
     */
    public function signature(string $text = '', string $msisdn = '', ?string $sender = null): string
    {
        return md5(md5($this->password) . $this->login . $text . $msisdn . ($sender ?? $this->senderName));
    }

    /**
     * Balans sorğusu üçün imza: `md5(md5(password) + login)`.
     */
    public function balanceSignature(): string
    {
        return md5(md5($this->password) . $this->login);
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
            throw new MissingConfiguration($variable, 'Set it, or pass the value to the LsimConfig constructor.');
        }

        return $value;
    }
}
