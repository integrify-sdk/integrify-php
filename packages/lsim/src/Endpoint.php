<?php

declare(strict_types=1);

namespace Integrify\Lsim;

/**
 * Tək SMS API-sinin endpoint-ləri (`https://apps.lsim.az`).
 */
enum Endpoint: string
{
    case SendSmsGet = '/quicksms/v1/send';
    case SendSmsPost = '/quicksms/v1/smssender';
    case Balance = '/quicksms/v1/balance';
    case ReportGet = '/quicksms/v1/report';
    case ReportPost = '/quicksms/v1/smsreporter';
}
