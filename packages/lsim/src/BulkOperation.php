<?php

declare(strict_types=1);

namespace Integrify\Lsim;

/**
 * Toplu SMS API-si tək endpoint-dir (`/smxml/api`); nə edildiyini payload-dakı
 * `operation` field-i bildirir.
 */
enum BulkOperation: string
{
    case Submit = 'submit';
    case Report = 'report';
    case DetailedReport = 'detailedreport';
    case DetailedReportWithDate = 'detailedreportwithdate';
    case Units = 'units';
}
