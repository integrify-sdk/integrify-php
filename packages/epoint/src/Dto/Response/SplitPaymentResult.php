<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\EPoint\Enum\BankCode;
use Integrify\EPoint\Enum\TransactionStatus;

/**
 * `splitPayWithSavedCard()` cavabı — `PaymentResult` üstəgəl bölünən məbləğ.
 */
final readonly class SplitPaymentResult extends Data
{
    /**
     * @param string|null $status Əməliyyatın nəticəsi.
     * @param string|null $message Əməliyyatın icra statusu haqqında mesaj.
     * @param string|null $transaction EPoint xidmətinin əməliyyat IDsi.
     * @param string|null $bankTransaction Bank ödəniş əməliyyatı IDsi.
     * @param string|null $bankResponse Bankın cavabı.
     * @param string|null $operationCode Əməliyyat kodu.
     * @param string|null $rrn Retrieval Reference Number.
     * @param string|null $cardMask Kart maskası.
     * @param string|null $cardName Kart sahibinin adı.
     * @param string|null $amount Ümumi ödəniş məbləği.
     * @param string|null $code Bankın cavab kodu.
     * @param string|null $splitAmount İkinci istifadəçiyə gedən məbləğ.
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
        #[Field(name: 'split_amount')]
        public ?string $splitAmount = null,
    ) {
    }

    public function status(): ?TransactionStatus
    {
        return TransactionStatus::parse($this->status);
    }

    public function isSuccessful(): bool
    {
        return TransactionStatus::succeeded($this->status);
    }

    public function codeMessage(): ?string
    {
        return BankCode::describe($this->code);
    }
}
