<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * `/api/order`-a edilən dörd sorğunun da cavabı.
 *
 * `redirectUrl()` müştərinin yönləndirilməli olduğu tam ünvanı qurur — bankın özü
 * hazır link qaytarmır, `hppUrl`, `id` və `password`-dan yığmaq lazımdır.
 *
 * `password` sifarişə aid birdəfəlik sirrdir və sonrakı `linkCardToken()` /
 * `processPaymentWithSavedCard()` sorğularında lazım olur. Merchant parolu ilə
 * qarışdırmayın.
 */
final readonly class CreatedOrder extends Data
{
    /**
     * @param int $id Sifariş IDsi.
     * @param string $password Sifarişin birdəfəlik parolu.
     * @param string $hppUrl Hosted Payment Page-in baza ünvanı.
     */
    public function __construct(
        public int $id,
        public string $password,
        #[Field(name: 'hppUrl')]
        public string $hppUrl,
    ) {
    }

    /**
     * Müştərinin yönləndiriləcəyi tam ünvan.
     */
    public function redirectUrl(): string
    {
        return sprintf('%s?id=%d&password=%s', $this->hppUrl, $this->id, rawurlencode($this->password));
    }
}
