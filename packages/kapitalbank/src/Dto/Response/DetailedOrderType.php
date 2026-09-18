<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Sifariş növünün tam təsviri — hansı əməliyyatlara icazə verildiyi.
 */
final readonly class DetailedOrderType extends Data
{
    /**
     * @param string|null $rid Növün identifikatoru (`Order_SMS`, `Order_DMS`, ...).
     * @param string|null $title Növün adı.
     * @param string|null $orderClass Sifarişin sinfi.
     * @param string|null $hppTranPhase HPP tranzaksiya mərhələsi.
     * @param int|null $secretLength Sifariş parolunun uzunluğu.
     * @param bool|null $allowVoid Ləğvə icazə verilirmi.
     * @param list<string>|null $paymentMethods İcazə verilən ödəniş üsulları.
     * @param list<string>|null $cardBrands İcazə verilən ödəniş sistemləri.
     * @param bool|null $allowTdsAttempt 3-D Secure cəhdinə icazə.
     * @param bool|null $allowTdsCant 3-D Secure mümkün olmadıqda icazə.
     * @param bool|null $allowTdsChallenged 3-D Secure challenge-a icazə.
     * @param bool|null $allowSurcharge Əlavə haqqa icazə.
     * @param bool|null $allowCvv2 CVV2-yə icazə.
     * @param list<string>|null $allowTranTypes İcazə verilən tranzaksiya növləri.
     * @param list<string>|null $allowTranPhases İcazə verilən mərhələlər.
     * @param list<string>|null $allowAuthKinds İcazə verilən avtorizasiya növləri.
     * @param list<string>|null $allowCofStoreUsages İcazə verilən CoF istifadələri.
     */
    public function __construct(
        public ?string $rid = null,
        public ?string $title = null,
        #[Field(name: 'orderClass')]
        public ?string $orderClass = null,
        #[Field(name: 'hppTranPhase')]
        public ?string $hppTranPhase = null,
        #[Field(name: 'secretLength')]
        public ?int $secretLength = null,
        #[Field(name: 'allowVoid')]
        public ?bool $allowVoid = null,
        #[Field(name: 'paymentMethods')]
        public ?array $paymentMethods = null,
        #[Field(name: 'cardBrands')]
        public ?array $cardBrands = null,
        #[Field(name: 'allowTdsAttempt')]
        public ?bool $allowTdsAttempt = null,
        #[Field(name: 'allowTdsCant')]
        public ?bool $allowTdsCant = null,
        #[Field(name: 'allowTdsChallenged')]
        public ?bool $allowTdsChallenged = null,
        #[Field(name: 'allowSurcharge')]
        public ?bool $allowSurcharge = null,
        #[Field(name: 'allowCVV2')]
        public ?bool $allowCvv2 = null,
        #[Field(name: 'allowTranTypes')]
        public ?array $allowTranTypes = null,
        #[Field(name: 'allowTranPhases')]
        public ?array $allowTranPhases = null,
        #[Field(name: 'allowAuthKinds')]
        public ?array $allowAuthKinds = null,
        #[Field(name: 'allowCofStoreUsages')]
        public ?array $allowCofStoreUsages = null,
    ) {
    }
}
