<?php

declare(strict_types=1);

namespace Integrify\EPoint;

/**
 * EPoint API-sinin endpoint-ləri (`https://epoint.az`).
 *
 * Yolların bəziləri adı ilə gözləniləni etmir — EPoint-in öz adlandırmasıdır və
 * dəyişdirilə bilməz:
 *
 * - `/api/1/refund-request` **payout**-dur (saxlanılmış karta pul köçürmə),
 * - `/api/1/reverse` isə əsl **refund**-dur (ödənişin geri qaytarılması).
 *
 * İkisini bir-biri ilə dəyişmək pulu səhv istiqamətdə hərəkət etdirir, ona görə
 * enum case adları niyyəti, `value` isə EPoint-in yolunu göstərir.
 */
enum Endpoint: string
{
    case Pay = '/api/1/request';
    case GetStatus = '/api/1/get-status';
    case SaveCard = '/api/1/card-registration';
    case PayWithSavedCard = '/api/1/execute-pay';
    case PayAndSaveCard = '/api/1/card-registration-with-pay';
    case Payout = '/api/1/refund-request';
    case Refund = '/api/1/reverse';
    case SplitPay = '/api/1/split-request';
    case SplitPayWithSavedCard = '/api/1/split-execute-pay';
    case SplitPayAndSaveCard = '/api/1/split-card-registration-with-pay';
}
