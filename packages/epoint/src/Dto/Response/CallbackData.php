<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\EPoint\Enum\BankCode;
use Integrify\EPoint\Enum\TransactionStatus;

/**
 * Callback-də gələn, decode olunmuş data.
 *
 * EPoint ödənişin nəticəsini sizin dashboard-da qeyd etdiyiniz URL-ə `POST` edir.
 * Xam body-ni `Callback::decode()`-a verin — imzanı yoxlayıb bu DTO-nu qaytarır.
 *
 * `order_id` sizin sorğuda göndərdiyiniz IDdir, yəni ödənişi öz bazanızdakı sifarişlə
 * bu field vasitəsilə uzlaşdırırsınız.
 */
final readonly class CallbackData extends Data
{
    /**
     * @param string|null $status Əməliyyatın nəticəsi.
     * @param string|null $message Əməliyyatın icra statusu haqqında mesaj.
     * @param string|null $transaction EPoint xidmətinin əməliyyat IDsi.
     * @param string|null $bankTransaction Bank ödəniş əməliyyatı IDsi.
     * @param string|null $bankResponse Bankın cavabı.
     * @param string|null $operationCode `001` — kart qeydiyyatı, `100` — istifadəçi ödənişi.
     * @param string|null $rrn Retrieval Reference Number.
     * @param string|null $cardMask Kart maskası.
     * @param string|null $cardName Kart sahibinin adı.
     * @param string|null $amount Ödəniş məbləği.
     * @param string|null $code Bankın cavab kodu.
     * @param string|null $orderId Sizin sorğuda göndərdiyiniz unikal sifariş IDsi.
     * @param string|null $cardId Qeyd olunmuş kartın identifikatoru.
     * @param string|null $splitAmount İkinci istifadəçiyə gedən məbləğ.
     * @param string|null $otherAttr Sorğuda göndərdiyiniz əlavə dəyərlər.
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
        #[Field(name: 'card_id')]
        public ?string $cardId = null,
        #[Field(name: 'split_amount')]
        public ?string $splitAmount = null,
        #[Field(name: 'other_attr')]
        public ?string $otherAttr = null,
    ) {
    }

    public function status(): ?TransactionStatus
    {
        return TransactionStatus::parse($this->status);
    }

    /** Ödəniş uğurlu oldumu. */
    public function isSuccessful(): bool
    {
        return TransactionStatus::succeeded($this->status);
    }

    public function codeMessage(): ?string
    {
        return BankCode::describe($this->code);
    }
}
