<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\Kapitalbank\Enum\OrderStatus;

/**
 * `getDetailedOrderInformation()` cavabı — sifarişin tam vəziyyəti.
 *
 * Bu, bankın verdiyi ən geniş cavabdır: kart, 3-D Secure, müştərinin brauzeri,
 * merchant və sifariş növünün bütün icazələri. Qısa forma üçün
 * [`OrderInformation`](OrderInformation.php).
 *
 * Məbləğ field-ləri **sətir** saxlanılır: bank onları JSON ədədi kimi qaytarır,
 * `float`-a çevirmək isə qəpik dəqiqliyini itirə bilər.
 *
 * `authorizedChargeAmount` və `clearedChargeAmount` fərqlidir: birincisi kartda
 * bloklanmış, ikincisi faktiki silinmiş məbləğdir. İkisinin fərqi hələ clearing
 * gözləyən məbləğdir.
 */
final readonly class DetailedOrderInformation extends Data
{
    /**
     * @param int|null $id Sifariş IDsi.
     * @param string|null $status Sifarişin vəziyyəti. Enum üçün `status()`.
     * @param string|null $prevStatus Əvvəlki vəziyyət.
     * @param string|null $lastStatusLogin Vəziyyəti sonuncu dəyişən istifadəçi.
     * @param string|null $amount Sifarişin məbləği.
     * @param string|null $currency Məzənnə.
     * @param string|null $hppUrl Hosted Payment Page-in ünvanı.
     * @param string|null $hppRedirectUrl Ödənişdən sonra qayıdılan URL.
     * @param string|null $password Sifarişin birdəfəlik parolu.
     * @param string|null $description Sifarişin təsviri.
     * @param string|null $language Ödəniş səhifəsinin dili.
     * @param string|null $createTime Yaradılma vaxtı.
     * @param string|null $finishTime Bitmə vaxtı.
     * @param string|null $srcAmount Mənbə məbləği.
     * @param string|null $srcAmountFull Mənbə məbləği (tam).
     * @param string|null $srcCurrency Mənbə məzənnəsi.
     * @param string|null $dstAmount Hədəf məbləği.
     * @param string|null $dstCurrency Hədəf məzənnəsi.
     * @param string|null $authorizedChargeAmount Bloklanmış məbləğ.
     * @param string|null $clearedChargeAmount Faktiki silinmiş məbləğ.
     * @param string|null $clearedRefundAmount Geri qaytarılmış məbləğ.
     * @param string|null $cvv2AuthStatus CVV2 yoxlamasının nəticəsi.
     * @param string|null $tdsV1AuthStatus 3-D Secure v1 nəticəsi.
     * @param string|null $tdsV2AuthStatus 3-D Secure v2 nəticəsi.
     * @param string|null $tdsServerUrl 3-D Secure serverinin ünvanı.
     * @param string|null $initiationEnvKind Sorğunun başladığı mühit.
     * @param list<StoredToken>|null $storedTokens CoF provayderindəki tokenlər.
     * @param SrcToken|null $srcToken Sifarişə bağlanmış kart tokeni.
     * @param ConsumerDevice|null $consumerDevice Müştərinin cihazı.
     * @param Merchant|null $merchant Merchant-ın məlumatları.
     * @param DetailedOrderType|null $type Sifariş növünün tam təsviri.
     * @param array<string, mixed>|null $terminal Terminalın məlumatları (sərbəst struktur).
     * @param array<string, mixed>|null $reportPubs Hesabat kanalları (sərbəst struktur).
     * @param list<string>|null $hppCofCapturePurposes Kartın saxlanma məqsədləri.
     * @param list<string>|null $custAttrs Merchant-ın əlavə atributları.
     */
    public function __construct(
        public ?int $id = null,
        public ?string $status = null,
        #[Field(name: 'prevStatus')]
        public ?string $prevStatus = null,
        #[Field(name: 'lastStatusLogin')]
        public ?string $lastStatusLogin = null,
        public ?string $amount = null,
        public ?string $currency = null,
        #[Field(name: 'hppUrl')]
        public ?string $hppUrl = null,
        #[Field(name: 'hppRedirectUrl')]
        public ?string $hppRedirectUrl = null,
        public ?string $password = null,
        public ?string $description = null,
        public ?string $language = null,
        #[Field(name: 'createTime')]
        public ?string $createTime = null,
        #[Field(name: 'finishTime')]
        public ?string $finishTime = null,
        #[Field(name: 'srcAmount')]
        public ?string $srcAmount = null,
        #[Field(name: 'srcAmountFull')]
        public ?string $srcAmountFull = null,
        #[Field(name: 'srcCurrency')]
        public ?string $srcCurrency = null,
        #[Field(name: 'dstAmount')]
        public ?string $dstAmount = null,
        #[Field(name: 'dstCurrency')]
        public ?string $dstCurrency = null,
        #[Field(name: 'authorizedChargeAmount')]
        public ?string $authorizedChargeAmount = null,
        #[Field(name: 'clearedChargeAmount')]
        public ?string $clearedChargeAmount = null,
        #[Field(name: 'clearedRefundAmount')]
        public ?string $clearedRefundAmount = null,
        #[Field(name: 'cvv2AuthStatus')]
        public ?string $cvv2AuthStatus = null,
        #[Field(name: 'tdsV1AuthStatus')]
        public ?string $tdsV1AuthStatus = null,
        #[Field(name: 'tdsV2AuthStatus')]
        public ?string $tdsV2AuthStatus = null,
        #[Field(name: 'tdsServerUrl')]
        public ?string $tdsServerUrl = null,
        #[Field(name: 'initiationEnvKind')]
        public ?string $initiationEnvKind = null,
        #[Field(name: 'storedTokens', of: StoredToken::class)]
        public ?array $storedTokens = null,
        #[Field(name: 'srcToken')]
        public ?SrcToken $srcToken = null,
        #[Field(name: 'consumerDevice')]
        public ?ConsumerDevice $consumerDevice = null,
        public ?Merchant $merchant = null,
        public ?DetailedOrderType $type = null,
        public ?array $terminal = null,
        #[Field(name: 'reportPubs')]
        public ?array $reportPubs = null,
        #[Field(name: 'hppCofCapturePurposes')]
        public ?array $hppCofCapturePurposes = null,
        #[Field(name: 'custAttrs')]
        public ?array $custAttrs = null,
    ) {
    }

    /** `status`-un enum qarşılığı, tanınırsa. */
    public function status(): ?OrderStatus
    {
        return $this->status === null ? null : OrderStatus::tryFrom($this->status);
    }

    /** Sifariş tam ödənilibmi. */
    public function isFullyPaid(): bool
    {
        return $this->status === OrderStatus::FullyPaid->value;
    }

    /**
     * Sonrakı ödənişlərdə istifadə oluna bilən kart tokeni, varsa.
     *
     * `saveCard()` axınından sonra `payWithSavedCard()`-a veriləcək dəyər budur.
     */
    public function cardToken(): ?int
    {
        return $this->srcToken?->id;
    }
}
