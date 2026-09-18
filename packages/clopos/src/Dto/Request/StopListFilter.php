<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Request;

use Integrify\Clopos\Enum\StopListFilterField;

/**
 * `getStopList()` üçün bir aralıq filtri.
 *
 * Bu, `Data` alt class-ı **deyil**: filtr JSON body-yə yox, query açarlarına çevrilir
 * (`filters[0][0]=id&filters[0][1][0]=0&filters[0][1][1]=100`).
 *
 * ```php
 * $client->getStopList(
 *     new StopListFilter(StopListFilterField::Id, from: 0, to: 100),
 * );
 * ```
 */
final readonly class StopListFilter
{
    /**
     * @param StopListFilterField $by Filtrlənən sahə.
     * @param int $from Aralığın başlanğıcı.
     * @param int|null $to Aralığın sonu. Verilməsə, yalnız başlanğıc göndərilir.
     */
    public function __construct(
        public StopListFilterField $by,
        public int $from,
        public ?int $to = null,
    ) {
    }
}
