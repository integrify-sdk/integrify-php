<?php

declare(strict_types=1);

namespace Integrify\Clopos\Enum;

/**
 * `listCustomers()` sorğusunda əlavə yüklənə bilən əlaqələr.
 *
 * Verilməsə, `Customer`-in uyğun field-ləri `null` qalır.
 */
enum CustomerRelation: string
{
    /** `Customer::$group` — müştəri qrupu. */
    case Group = 'group';

    /** `Customer::$balance` — mağaza krediti balansı. */
    case Balance = 'balance';

    /** `Customer::$cashbackBalance` — keşbek balansı. */
    case CashbackBalance = 'cashback_balance';
}
