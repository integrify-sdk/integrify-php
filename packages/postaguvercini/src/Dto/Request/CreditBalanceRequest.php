<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Request;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `checkBalance()` sorğusunun payload-u — yalnız hesabın məlumatları.
 */
final readonly class CreditBalanceRequest extends Data
{
    /**
     * @param string $username Hesabın istifadəçi adı.
     * @param string $password Hesabın parolu.
     */
    public function __construct(
        #[Field(name: 'Username')]
        public string $username = '',
        #[Field(name: 'Password')]
        public string $password = '',
    ) {
    }
}
