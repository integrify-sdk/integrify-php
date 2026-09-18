<?php

declare(strict_types=1);

namespace Integrify\Azericard\Dto\Response;

use Integrify\Azericard\Enum\TransferStatusCode;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `confirmTransfer()` və `declineTransfer()` cavabı.
 *
 * `rrn` və `receiverPan` yalnız təsdiq cavabında gəlir; rədd cavabında `null` qalır.
 *
 * **İmza burada avtomatik yoxlanılmır.** Yoxlama `Callback::verifyTransferResult()`
 * ilə açıq şəkildə aparılır, çünki imza mənbəyi (MT açarı) DTO-ya məlum deyil — və
 * imzanın DTO qurulanda "gizlicə" yoxlanması, uğursuzluqda anlaşılmaz bir validasiya
 * xətası verərdi.
 */
final readonly class TransferResult extends Data
{
    /**
     * @param string|null $operationId Azericard-ın əməliyyat IDsi.
     * @param string|null $srn Sizin unikal əməliyyat nömrəniz.
     * @param string|null $rrn Retrieval Reference Number — yalnız təsdiqdə.
     * @param string|null $amount Məbləğ. **Sətir** saxlanılır.
     * @param string|null $currency Valyuta.
     * @param string|null $receiverPan Maskalanmış kart nömrəsi — yalnız təsdiqdə.
     * @param string|null $status Vəziyyət mesajı.
     * @param string|null $timestamp Cavabın vaxtı.
     * @param string|null $responseCode Uğur(suz)luq kodu. Enum üçün `code()`.
     * @param string|null $message Mesaj.
     * @param string|null $signature Azericard-ın MD5 imzası.
     */
    public function __construct(
        #[Field(name: 'OperationId')]
        public ?string $operationId = null,
        #[Field(name: 'SRN')]
        public ?string $srn = null,
        #[Field(name: 'RRN')]
        public ?string $rrn = null,
        #[Field(name: 'Amount')]
        public ?string $amount = null,
        #[Field(name: 'Cur')]
        public ?string $currency = null,
        #[Field(name: 'ReceiverPan')]
        public ?string $receiverPan = null,
        #[Field(name: 'Status')]
        public ?string $status = null,
        #[Field(name: 'Timestamp')]
        public ?string $timestamp = null,
        #[Field(name: 'ResponseCode')]
        public ?string $responseCode = null,
        #[Field(name: 'Message')]
        public ?string $message = null,
        #[Field(name: 'Signature')]
        public ?string $signature = null,
    ) {
    }

    /** `responseCode`-un enum qarşılığı, tanınırsa. */
    public function code(): ?TransferStatusCode
    {
        return $this->responseCode === null ? null : TransferStatusCode::tryFrom($this->responseCode);
    }

    /** Yalnız `0` uğurdur. */
    public function isSuccessful(): bool
    {
        return $this->responseCode === TransferStatusCode::Success->value;
    }
}
