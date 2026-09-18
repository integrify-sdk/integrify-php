<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini;

use Integrify\Exception\MissingConfiguration;

/**
 * Posta Güvercini hesabının məlumatları.
 *
 * Dəyişməz value object-dir və klientə konstruktorda ötürülür — qlobal state və ya
 * sorğu anında environment oxuması yoxdur. `fromEnvironment()` adlandırılmış
 * konstruktordur, yəni environment yalnız siz istəyəndə oxunur.
 *
 * ```php
 * $config = new PostaGuverciniConfig(username: 'my-user', password: 'secret');
 * // və ya
 * $config = PostaGuverciniConfig::fromEnvironment();
 * ```
 *
 * > [!WARNING]
 * > Bu servis **parolu hər sorğunun body-sində** göndərir (`Username`/`Password`
 * > field-ləri), header-də və ya imzada yox. Yəni sorğu body-ləri log-a düşməməlidir:
 * > LSIM-dən fərqli olaraq burada parol hash-lənmir, EPoint-dən fərqli olaraq
 * > imzalanmır — olduğu kimi gedir.
 */
final readonly class PostaGuverciniConfig
{
    /**
     * @param string $username Hesabın istifadəçi adı.
     * @param string $password Hesabın parolu. **Hər sorğunun payload-una düşür.**
     * @param string|null $originator Göndərən adı. `null` — hesabın default-u işləyir.
     */
    public function __construct(
        public string $username,
        public string $password,
        public ?string $originator = null,
    ) {
    }

    /**
     * Environment dəyişənlərindən qurur.
     *
     * | Dəyişən | Məcburi |
     * | :--- | :--- |
     * | `POSTA_GUVERCINI_USERNAME` | bəli |
     * | `POSTA_GUVERCINI_PASSWORD` | bəli |
     * | `POSTA_GUVERCINI_ORIGINATOR` | xeyr |
     *
     * Son dəyişən Python kitabxanasında yoxdur — orada `originator` yalnız metod
     * arqumentidir. Burada konfiqurasiyaya da qoyulub, çünki göndərən adı demək olar
     * həmişə hesab səviyyəsində sabitdir; metod arqumenti onu yenə üstələyir.
     *
     * @throws MissingConfiguration Məcburi dəyişən yoxdursa.
     */
    public static function fromEnvironment(): self
    {
        $originator = self::read('POSTA_GUVERCINI_ORIGINATOR', required: false);

        return new self(
            username: self::read('POSTA_GUVERCINI_USERNAME'),
            password: self::read('POSTA_GUVERCINI_PASSWORD'),
            originator: $originator === '' ? null : $originator,
        );
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
            throw new MissingConfiguration(
                $variable,
                'Set it, or pass the value to the PostaGuverciniConfig constructor.',
            );
        }

        return $value;
    }
}
