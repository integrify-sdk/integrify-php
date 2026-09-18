<?php

declare(strict_types=1);

namespace Integrify\Clopos;

/**
 * Clopos Open API v2-nin endpoint-ləri.
 *
 * Yollar baza url-ə nisbətəndir (`https://integrations.clopos.com/open-api/v2/`).
 * Dəyişən hissə `{id}` şəklindədir və `Client::uri()` tərəfindən doldurulur —
 * sətir birləşdirməsi ilə deyil, ona görə `id` içindəki `/` və ya `?` url-i dəyişə
 * bilmir.
 */
enum Endpoint: string
{
    /** Token alınması — yeganə açarsız endpoint. */
    case Auth = 'auth';

    case Venues = 'venues';

    case Users = 'users';
    case User = 'users/{id}';

    case Customers = 'customers';
    case Customer = 'customers/{id}';
    case CustomerGroups = 'customer-groups';

    case Categories = 'categories';
    case Category = 'categories/{id}';

    case Stations = 'stations';
    case Station = 'stations/{id}';

    case Products = 'products';
    case Product = 'products/{id}';
    case StopList = 'products/stop-list';

    case SaleTypes = 'sale-types';
    case PaymentMethods = 'payment-methods';

    case Orders = 'orders';
    case Order = 'orders/{id}';

    case Receipts = 'receipts';
    case Receipt = 'receipts/{id}';
    case ReceiptClose = 'receipts/{id}/close';
    case ReceiptStockOperations = 'receipts/{id}/stock-operations';

    case PriceLists = 'price-lists';
    case PriceListPrices = 'price-lists/prices';
}
