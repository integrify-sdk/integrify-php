<?php

declare(strict_types=1);

namespace Integrify\EPoint\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\EPoint\Enum\BankCode;
use Integrify\EPoint\Enum\TransactionStatus;

/**
 * Server tərəfdə icra olunan ödənişlərin cavabı — `payWithSavedCard()` və
 * `payout()` bunu qaytarır.
 *
 * `rrn`, `cardMask` və `cardName` yalnız uğurlu əməliyyatda gəlir.
 */
final readonly class PaymentResult extends Data
{
    /**
     * @param string|null $status Əməliyyatın nəticəsi.
     * @param string|null $message Əməliyyatın icra statusu haqqında mesaj.
     * @param string|null $transaction EPoint xidmətinin əməliyyat IDsi.
     * @param string|null $bankTransaction Bank ödəniş əməliyyatı IDsi.
     * @param string|null $bankResponse Ödənişin nəticəsi ilə bankın cavabı.
     * @param string|null $operationCode `001` — kart qeydiyyatı, `100` — istifadəçi ödənişi.
     * @param string|null $rrn Retrieval Reference Number — unikal əməliyyat identifikatoru.
     *     Yalnız uğurlu əməliyyat üçün mövcuddur.
     * @param string|null $cardMask `123456******1234` formatında kart maskası.
     * @param string|null $cardName Ödəniş səhifəsində göstərilən kart sahibinin adı.
     * @param string|null $amount Ödəniş məbləği. **Sətir** saxlanılır: `float`-a
     *     çevirmək qəpik dəqiqliyini itirə bilər (`10.55` → `10.550000000000001`).
     * @param string|null $code Bankın 3 simvollu cavab kodu. İzahı üçün `codeMessage()`.
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

    /**
     * Bank kodunun izahı, tanınırsa.
     *
     * Xam kod `$code`-da qalır. Python kitabxanası kodun **özünü** izah mətni ilə
     * əvəz edir, yəni `'000'` ilə müqayisə etmək mümkün olmur; burada ikisi də
     * əlçatandır.
     */
    public function codeMessage(): ?string
    {
        return BankCode::describe($this->code);
    }
}
