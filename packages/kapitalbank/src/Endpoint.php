<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank;

/**
 * Kapital Bank e-commerce API-sinin endpoint-ləri.
 *
 * Dəyişən hissələr `{orderId}` şəklindədir və `Client::uri()` vasitəsilə
 * `rawurlencode` olunur.
 *
 * Diqqət: `ExecuteTransaction` **dörd** ayrı əməliyyat üçün istifadə olunur —
 * refund, tam geri qaytarma, clearing və yarımçıq geri qaytarma. Fərq yalnız
 * payload-dakı `phase`/`type`/`voidKind` dəyərlərindədir, yolda deyil.
 */
enum Endpoint: string
{
    case Order = '/api/order';
    case GetOrder = '/api/order/{orderId}';
    case ExecuteTransaction = '/api/order/{orderId}/exec-tran';
    case LinkCardToken = '/api/order/{orderId}/set-src-token';
}
