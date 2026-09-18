<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Dto\Request;

use Integrify\Dto\Data;

/**
 * `refundOrder()` tranzaksiyasının payload-u.
 *
 * Diqqət: bu DTO-nun field adları **camelCase deyil**. Python-da `/api/order` sxemləri
 * `to_camel` alias generator-undan istifadə edir, `exec-tran` sxemləri isə etmir —
 * ona görə burada `phase` və `type` olduğu kimi gedir. Uyğunsuzluq bankın API-sindədir,
 * bizim tərəfdə deyil.
 */
final readonly class RefundRequest extends Data
{
    /**
     * @param string $amount Geri qaytarılacaq məbləğ, sətir formatında.
     * @param string $phase Tranzaksiyanın mərhələsi.
     * @param string $type Tranzaksiyanın növü.
     */
    public function __construct(
        public string $amount,
        public string $phase = 'Single',
        public string $type = 'Refund',
    ) {
    }
}
