<?php

declare(strict_types=1);

namespace Integrify\EPoint\Enum;

/**
 * `getTransactionStatus()` cavabının `status` dəyərləri.
 *
 * Ödəniş sorğularından fərqli olaraq burada tranzaksiyanın **ömür dövrü** qaytarılır,
 * ona görə `new` və `returned` da var, `failed` isə yoxdur — bax:
 * [`TransactionStatus`](TransactionStatus.php).
 */
enum TransactionStatusExtended: string
{
    case New = 'new';
    case Success = 'success';
    case Returned = 'returned';
    case Error = 'error';
    case ServerError = 'server_error';

    public static function parse(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    /**
     * **Sorğunun** (tranzaksiyanın deyil) uğurlu olması.
     *
     * Status sorğusunda `error` normal cavabdır: "soruşduğun tranzaksiya uğursuz
     * olub". Sorğunun özü yalnız `server_error` halında uğursuzdur — məsələn imza
     * uyğun gəlməyəndə. Python kitabxanası da `ok`-u məhz belə hesablayır.
     *
     * Tranzaksiyanın uğurlu olub-olmaması ayrı sualdır: `$status === 'success'`.
     */
    public static function requestSucceeded(?string $value): bool
    {
        return $value !== self::ServerError->value;
    }
}
