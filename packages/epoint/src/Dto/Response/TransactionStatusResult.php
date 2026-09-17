<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\EPoint\Enum\BankCode;
use Integrify\EPoint\Enum\TransactionStatusExtended;

/**
 * `getTransactionStatus()` cavabı.
 *
 * Burada `isSuccessful()` digər cavablardan **fərqli** işləyir. Status sorğusunda
 * `error` normal cavabdır — "soruşduğun tranzaksiya uğursuz olub" — yəni sorğunun
 * özü uğurludur. Ona görə:
 *
 * - `isSuccessful()` — **sorğu** alındımı (yalnız `server_error` halında `false`);
 * - `isPaid()` — **tranzaksiya** uğurlu oldumu (`status === 'success'`).
 *
 * İkisini bir-biri ilə dəyişmək uğursuz ödənişi uğurlu kimi oxumaq deməkdir.
 */
final readonly class TransactionStatusResult extends Data
{
    /**
     * @param string|null $status `new`, `success`, `returned`, `error` və ya `server_error`.
     * @param string|null $message Status haqqında mesaj.
     * @param string|null $transaction EPoint xidmətinin əməliyyat IDsi.
     * @param string|null $bankTransaction Bank ödəniş əməliyyatı IDsi.
     * @param string|null $bankResponse Bankın cavabı.
     * @param string|null $operationCode Əməliyyat kodu.
     * @param string|null $rrn Retrieval Reference Number.
     * @param string|null $cardMask Kart maskası.
     * @param string|null $cardName Kart sahibinin adı.
     * @param string|null $amount Ödəniş məbləği.
     * @param string|null $code Bankın cavab kodu.
     * @param string|null $orderId Tətbiqinizdə unikal əməliyyat IDsi.
     * @param string|null $otherAttr Sorğuda göndərdiyiniz əlavə dəyərlər. EPoint
     *     bunu **sətir** kimi qaytarır, göndərdiyiniz obyekt kimi deyil.
     */
    public function __construct(
        public ?string $status = null,
        public ?string $message = null,
        public ?string $transaction = null,
        #[Field(name: 'bank_transaction')]
        public ?string $bankTransaction = null,
        #[Field(name: 'bank_response')]
        public ?string $bankResponse = null,
        #[Field(name: 'operation_code')]
        public ?string $operationCode = null,
        public ?string $rrn = null,
        #[Field(name: 'card_mask')]
        public ?string $cardMask = null,
        #[Field(name: 'card_name')]
        public ?string $cardName = null,
        public ?string $amount = null,
        public ?string $code = null,
        #[Field(name: 'order_id', maxLength: 255)]
        public ?string $orderId = null,
        #[Field(name: 'other_attr')]
        public ?string $otherAttr = null,
    ) {
    }

    /** `status`-un enum qarşılığı, tanınırsa. */
    public function status(): ?TransactionStatusExtended
    {
        return TransactionStatusExtended::parse($this->status);
    }

    /**
     * **Sorğu** uğurlu alındımı. Tranzaksiyanın nəticəsi üçün `isPaid()`.
     */
    public function isSuccessful(): bool
    {
        return TransactionStatusExtended::requestSucceeded($this->status);
    }

    /** Tranzaksiya uğurla ödənildimi. */
    public function isPaid(): bool
    {
        return $this->status === TransactionStatusExtended::Success->value;
    }

    /** Ödəniş geri qaytarılıbmı. */
    public function isReturned(): bool
    {
        return $this->status === TransactionStatusExtended::Returned->value;
    }

    public function codeMessage(): ?string
    {
        return BankCode::describe($this->code);
    }
}
