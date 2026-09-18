<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `authenticate()` cavabı — bir saat yaşayan token.
 *
 * Token `CloposClient`-in içində saxlanılır və növbəti sorğularda `x-token` header-i
 * kimi gedir; onu əlinizlə daşımağa ehtiyac yoxdur. Yenidən istifadə üçün (məs.
 * cache-ə yazmaq) `token` və `expiresAt` açıqdır.
 */
final readonly class AuthToken extends Data
{
    /**
     * @param string $token Sorğuları avtorizasiya edən JWT. Brend, filial və inteqrator
     *     onun içində kodlanıb — ona görə ayrıca `x-brand` header-i lazım deyil.
     * @param string|null $tokenType Token növü (`Bearer`).
     * @param int|null $expiresIn Neçə saniyə sonra bitəcəyi.
     * @param int|null $expiresAt Bitmə vaxtının unix dəyəri.
     * @param bool|null $success Servisin uğur bayrağı.
     * @param string|null $message Servisin mesajı.
     */
    public function __construct(
        public string $token,
        #[Field(name: 'token_type')]
        public ?string $tokenType = null,
        #[Field(name: 'expires_in')]
        public ?int $expiresIn = null,
        #[Field(name: 'expires_at')]
        public ?int $expiresAt = null,
        public ?bool $success = null,
        public ?string $message = null,
    ) {
    }
}
