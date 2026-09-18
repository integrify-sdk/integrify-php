<?php

declare(strict_types=1);

namespace Integrify\Azericard\Dto\Response;

use Integrify\Azericard\Enum\CardStatus;
use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Pul köçürməsinin nəticəsi — Azericard-ın callback URL-inizə post etdiyi data.
 *
 * Kart callback-indən fərqli olaraq bunun imzası **yoxlanıla bilir**, çünki MD5
 * paylaşılan açarla hesablanır. `Callback::decodeTransfer()` yoxlamanı özü aparır.
 */
final readonly class TransferCallback extends Data
{
    /**
     * @param string|null $operationId Azericard-ın əməliyyat IDsi.
     * @param string|null $srn Sizin unikal əməliyyat nömrəniz.
     * @param string|null $amount Məbləğ. **Sətir** saxlanılır.
     * @param string|null $currency Valyuta — yalnız AZN.
     * @param string|null $cardStatus Kartın vəziyyəti. Enum üçün `cardStatus()`.
     * @param string|null $receiverPan Maskalanmış kart nömrəsi.
     * @param string|null $status Cari tranzaksiya statusu (məs. `pending`).
     * @param string|null $timestamp Cavabın vaxtı.
     * @param string|null $responseCode Cavab kodu.
     * @param string|null $message Cavab mesajı.
     * @param string|null $signature Azericard-ın MD5 imzası.
     */
    public function __construct(
        #[Field(name: 'OperationID')]
        public ?string $operationId = null,
        #[Field(name: 'SRN')]
        public ?string $srn = null,
        #[Field(name: 'Amount')]
        public ?string $amount = null,
        #[Field(name: 'Cur')]
        public ?string $currency = null,
        #[Field(name: 'CardStatus')]
        public ?string $cardStatus = null,
        #[Field(name: 'ReceiverPAN')]
        public ?string $receiverPan = null,
        #[Field(name: 'Status')]
        public ?string $status = null,
        #[Field(name: 'Timestamp')]
        public ?string $timestamp = null,
        #[Field(name: 'Response Code')]
        public ?string $responseCode = null,
        #[Field(name: 'Message')]
        public ?string $message = null,
        #[Field(name: 'Signature')]
        public ?string $signature = null,
    ) {
    }

    /** `cardStatus`-un enum qarşılığı, tanınırsa. */
    public function cardStatus(): ?CardStatus
    {
        return $this->cardStatus === null ? null : CardStatus::tryFrom($this->cardStatus);
    }
}
