<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Request;

use Integrify\Clopos\Enum\CustomerFilterField;

/**
 * `listCustomers()` üçün bir filtr.
 *
 * Bu, `Data` alt class-ı **deyil**: filtr JSON body-yə yox, query açarlarına çevrilir
 * (`filters[0][0]=name&filters[0][1]=John`), ona görə DTO mapper-i burada işləmir.
 *
 * ```php
 * $client->listCustomers(filters: [
 *     new CustomerFilter(CustomerFilterField::Name, 'John Doe'),
 * ]);
 * ```
 */
final readonly class CustomerFilter
{
    /**
     * @param CustomerFilterField $by Filtrlənən sahə.
     * @param string $value Axtarılan dəyər.
     */
    public function __construct(
        public CustomerFilterField $by,
        public string $value,
    ) {
    }
}
