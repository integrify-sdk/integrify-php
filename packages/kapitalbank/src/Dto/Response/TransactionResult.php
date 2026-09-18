<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\Kapitalbank\Enum\PmoResultCode;

/**
 * `exec-tran` tranzaksiyalarının cavabı — refund, tam/yarımçıq ləğv, clearing və
 * saxlanılmış kartla ödəniş.
 *
 * `approvalCode` yalnız bəzi tranzaksiyalarda (refund, saxlanılmış kartla ödəniş)
 * gəlir, ona görə nullable-dir.
 */
final readonly class TransactionResult extends Data
{
    /**
     * @param TransactionMatch|null $match Tranzaksiyanın bank identifikatorları.
     * @param string|null $pmoResultCode PMO-nun nəticə kodu. İzahı üçün `resultMessage()`.
     * @param string|null $approvalCode Bankın təsdiq kodu.
     */
    public function __construct(
        public ?TransactionMatch $match = null,
        #[Field(name: 'pmoResultCode')]
        public ?string $pmoResultCode = null,
        #[Field(name: 'approvalCode')]
        public ?string $approvalCode = null,
    ) {
    }

    /**
     * PMO kodunun izahı, tanınırsa.
     *
     * Xam kod `$pmoResultCode`-da qalır. Python kitabxanası kodun **özünü** izah
     * mətni ilə əvəz edir və tanınmayan kod üçün `KeyError` atır — yəni bank yeni
     * kod qaytaran gün cavabın parse-i sınır.
     */
    public function resultMessage(): ?string
    {
        return PmoResultCode::describe($this->pmoResultCode);
    }

    /**
     * PMO tranzaksiyanı təsdiqlədimi.
     *
     * `'1'` — Approved. Digər kodlar ya rədd, ya da əlavə addım tələb edir; siyahı
     * üçün `PmoResultCode::all()`.
     */
    public function isApproved(): bool
    {
        return $this->pmoResultCode === '1';
    }
}
