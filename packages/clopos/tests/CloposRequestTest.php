<?php

declare(strict_types=1);

namespace Integrify\Clopos\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Integrify\Clopos\CloposClient;
use Integrify\Clopos\Dto\Request\CustomerFilter;
use Integrify\Clopos\Dto\Request\ProductFilter;
use Integrify\Clopos\Dto\Request\ReceiptPayment;
use Integrify\Clopos\Dto\Request\StopListFilter;
use Integrify\Clopos\Enum\CategoryType;
use Integrify\Clopos\Enum\CustomerFilterField;
use Integrify\Clopos\Enum\CustomerRelation;
use Integrify\Clopos\Enum\Gender;
use Integrify\Clopos\Enum\OrderRelation;
use Integrify\Clopos\Enum\OrderStatus;
use Integrify\Clopos\Enum\ProductRelation;
use Integrify\Clopos\Enum\ProductType;
use Integrify\Clopos\Enum\StopListFilterField;
use Integrify\Http\RecordingTransport;
use PHPUnit\Framework\TestCase;

/**
 * Məftilə nə çıxır.
 */
final class CloposRequestTest extends TestCase
{
    private RecordingTransport $transport;

    private CloposClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::client($this->transport);
    }

    /**
     * @return array<string, mixed>
     */
    private function query(): array
    {
        return $this->transport->lastRequest()->query;
    }

    /**
     * @return array<string, mixed>
     */
    private function body(): array
    {
        $body = $this->transport->lastRequest()->body;
        $this->assertIsArray($body);

        /** @var array<string, mixed> $body */
        return $body;
    }

    public function testAuthenticationPostsTheCredentialsWithoutAToken(): void
    {
        $client = Factory::client($this->transport, token: null);
        $this->transport->queue(Factory::authToken());

        $token = $client->authenticate();

        $request = $this->transport->lastRequest();

        $this->assertSame('POST', $request->method);
        $this->assertSame('https://integrations.clopos.com/open-api/v2/auth', $request->uri);
        $this->assertSame([
            'client_id' => 'cid',
            'client_secret' => 'secret',
            'brand' => 'brand',
            'integrator_id' => '7',
        ], $request->body);

        // Köhnə token yeni token istəyərkən mənasızdır — göndərilmir.
        $this->assertArrayNotHasKey('x-token', $request->headers);
        $this->assertSame('oauth_abc', $token->token);
    }

    public function testTheTokenFromAuthenticationIsUsedOnTheNextRequest(): void
    {
        $client = Factory::client($this->transport, token: null);

        $this->transport->queue(Factory::authToken());
        $client->authenticate();

        $this->transport->queue(Factory::listResponse());
        $client->listVenues();

        $this->assertSame('oauth_abc', $client->token());
        $this->assertSame('oauth_abc', $this->transport->lastRequest()->headers['x-token']);
    }

    public function testTheVenueHeaderIsSentOnlyWhenConfigured(): void
    {
        $this->transport->queue(Factory::listResponse());
        $this->client->listVenues();
        $this->assertArrayNotHasKey('x-venue', $this->transport->lastRequest()->headers);

        $client = Factory::client($this->transport, Factory::config(venueId: Factory::VENUE_ID));
        $this->transport->queue(Factory::listResponse());
        $client->listVenues();

        $this->assertSame(Factory::VENUE_ID, $this->transport->lastRequest()->headers['x-venue']);
    }

    public function testNoBrandHeaderIsSentBecauseTheTokenCarriesIt(): void
    {
        $this->transport->queue(Factory::listResponse());
        $this->client->listVenues();

        // v2-də brend, filial və inteqrator JWT-nin içindədir.
        $this->assertArrayNotHasKey('x-brand', $this->transport->lastRequest()->headers);
    }

    public function testPaginationIsOmittedWhenNotGiven(): void
    {
        $this->transport->queue(Factory::listResponse());
        $this->client->listVenues();

        $this->assertSame([], $this->query());
    }

    public function testPaginationIsSentWhenGiven(): void
    {
        $this->transport->queue(Factory::listResponse());
        $this->client->listVenues(2, 10);

        $this->assertSame(['page' => 2, 'limit' => 10], $this->query());
    }

    public function testBooleanQueryParametersAreTrueAndFalseNotOneAndEmpty(): void
    {
        $this->transport->queue(Factory::listResponse());
        $this->client->listCategories(includeChildren: true, includeInactive: false);

        // `http_build_query()` `false`-u BOŞ SƏTİR kimi yazır — filtr tamamilə itərdi.
        $this->assertSame(['include_children' => 'true', 'include_inactive' => 'false'], $this->query());
        $this->assertSame(
            'include_children=true&include_inactive=false',
            http_build_query($this->query()),
        );
    }

    public function testCategoryFiltersAreSentAsPlainParameters(): void
    {
        $this->transport->queue(Factory::listResponse());
        $this->client->listCategories(1, 50, parentId: 3, type: CategoryType::Product);

        $this->assertSame(
            ['page' => 1, 'limit' => 50, 'parent_id' => 3, 'type' => 'PRODUCT'],
            $this->query(),
        );
    }

    public function testCustomerRelationsAndFiltersAreIndexed(): void
    {
        $this->transport->queue(Factory::listResponse());

        $this->client->listCustomers(
            1,
            20,
            with: [CustomerRelation::Group, CustomerRelation::Balance],
            filters: [
                new CustomerFilter(CustomerFilterField::Name, 'John Doe'),
                new CustomerFilter(CustomerFilterField::Phones, '+1234567890'),
            ],
        );

        $this->assertSame([
            // Python burada səhifələməni tamamilə itirir: `GetCustomersRequest`-in
            // `@model_serializer`-i bütün dump-ı əvəz edir və `page`/`limit` düşür.
            'page' => 1,
            'limit' => 20,
            'with[0]' => 'group',
            'with[1]' => 'balance',
            // Python `filter[0][1]` yazır — tək halda. Dəyər yarısı serverə çatmır.
            'filters[0][0]' => 'name',
            'filters[0][1]' => 'John Doe',
            'filters[1][0]' => 'phones',
            'filters[1][1]' => '+1234567890',
        ], $this->query());
    }

    public function testProductFiltersAreRealQueryParametersNotAJsonBlob(): void
    {
        $this->transport->queue(Factory::listResponse());

        $this->client->listProducts(
            1,
            50,
            selects: ['id', 'name'],
            filters: new ProductFilter(
                type: [ProductType::Goods, ProductType::Dish],
                categoryId: [1, 2],
                giftable: true,
            ),
        );

        $this->assertSame([
            'page' => 1,
            'limit' => 50,
            'selects[]' => 'id,name',
            'filters[type][0]' => 'type',
            'filters[type][1][0]' => 'GOODS',
            'filters[type][1][1]' => 'DISH',
            'filters[category_id][0]' => 'category_id',
            'filters[category_id][1][0]' => 1,
            'filters[category_id][1][1]' => 2,
            'filters[giftable][0]' => 'giftable',
            'filters[giftable][1]' => 1,
        ], $this->query());

        // Python bütün payload-u `json.dumps()` edib httpx-ə `params` kimi verir, yəni
        // query string tək bir JSON blobu olur və heç bir filtr serverə çatmır.
        $this->assertStringContainsString('filters%5Bgiftable%5D%5B1%5D=1', http_build_query($this->query()));
    }

    public function testSelectsAcceptsAReadyCommaSeparatedString(): void
    {
        $this->transport->queue(Factory::listResponse());
        $this->client->listProducts(selects: 'id,name');

        $this->assertSame(['selects[]' => 'id,name'], $this->query());
    }

    public function testAFalseProductFilterIsSentAsZeroNotDropped(): void
    {
        $this->transport->queue(Factory::listResponse());
        $this->client->listProducts(filters: new ProductFilter(giftable: false));

        $this->assertSame(
            ['filters[giftable][0]' => 'giftable', 'filters[giftable][1]' => 0],
            $this->query(),
        );
    }

    public function testProductRelationsUseIndexedWithKeys(): void
    {
        $this->transport->queue(Factory::objectResponse(['id' => 1]));

        $this->client->getProduct(1, [ProductRelation::Modifications, ProductRelation::Recipe]);

        $this->assertSame('https://integrations.clopos.com/open-api/v2/products/1', $this->transport->lastRequest()->uri);
        $this->assertSame(['with[0]' => 'modifications', 'with[1]' => 'recipe'], $this->query());
    }

    public function testTheStopListFilterSendsARangeAndDropsTheOpenEnd(): void
    {
        $this->transport->queue(Factory::listResponse());

        $this->client->getStopList(
            new StopListFilter(StopListFilterField::Id, 0, 100),
            new StopListFilter(StopListFilterField::Limit, 1),
        );

        $this->assertSame([
            'filters[0][0]' => 'id',
            'filters[0][1][0]' => 0,
            'filters[0][1][1]' => 100,
            'filters[1][0]' => 'limit',
            'filters[1][1][0]' => 1,
        ], $this->query());
    }

    public function testTheOrderRelationIsSentAsWithNotWithUnderscore(): void
    {
        $this->transport->queue(Factory::objectResponse(['id' => 4]));

        $this->client->getOrder(4, OrderRelation::ReceiptId);

        // Python `with_` göndərir — sorğu modelində serialization alias yoxdur, ona görə
        // Python tərəfdəki property adı olduğu kimi məftilə düşür.
        $this->assertSame(['with' => 'receipt:id'], $this->query());
    }

    public function testCreatingACustomerDropsEveryUnsetField(): void
    {
        $this->transport->queue(Factory::objectResponse(['id' => 1, 'name' => 'John Doe']));

        $this->client->createCustomer(
            name: 'John Doe',
            email: 'j@e.com',
            description: 'Test',
            groupId: 1,
            gender: Gender::Male,
            dateOfBirth: '1990-05-15',
        );

        $this->assertSame([
            'name' => 'John Doe',
            'email' => 'j@e.com',
            'description' => 'Test',
            'group_id' => 1,
            'gender' => 1,
            'date_of_birth' => '1990-05-15',
        ], $this->body());
    }

    public function testADateOfBirthObjectIsFormattedAsAPlainDate(): void
    {
        $this->transport->queue(Factory::objectResponse(['id' => 1]));

        $this->client->createCustomer('X', dateOfBirth: new DateTimeImmutable('1990-05-15 13:45:00'));

        $this->assertSame('1990-05-15', $this->body()['date_of_birth']);
    }

    public function testANewOrderKeepsTwoNullsAndDropsTheAddress(): void
    {
        $this->transport->queue(Factory::objectResponse(['id' => 1]));

        $this->client->createOrder(1, Factory::order(), ['comment' => '']);

        $this->assertSame([
            'customer_id' => 1,
            'payload' => [
                'service' => [
                    'sale_type_id' => 2,
                    'sale_type_name' => 'Delivery',
                    'venue_id' => 1,
                    'venue_name' => 'Main',
                ],
                'customer' => [
                    'id' => 9,
                    'name' => 'Rashid',
                    // Python modelində bu ikisinin default-u `UNSET` deyil, `None`-dur,
                    // ona görə payload-dan çıxmır.
                    'customer_discount_type' => null,
                    'phone' => null,
                ],
                'products' => [['product_id' => 1, 'count' => 2]],
            ],
            'meta' => ['comment' => ''],
        ], $this->body());
    }

    public function testAnOrderAddressIsSentWhenGiven(): void
    {
        $this->transport->queue(Factory::objectResponse(['id' => 1]));

        $this->client->createOrder(1, Factory::order(address: 'Nizami 1'));

        $body = $this->body();
        $this->assertIsArray($body['payload']);
        $this->assertIsArray($body['payload']['customer']);
        $this->assertSame('Nizami 1', $body['payload']['customer']['address']);
        $this->assertArrayNotHasKey('meta', $body);
    }

    public function testUpdatingAnOrderStatusSendsTheIdOnlyInTheUrl(): void
    {
        $this->transport->queue(Factory::objectResponse(['id' => 3]));

        $this->client->updateOrderStatus(3, OrderStatus::Confirmed);

        $request = $this->transport->lastRequest();

        $this->assertSame('PUT', $request->method);
        $this->assertSame('https://integrations.clopos.com/open-api/v2/orders/3', $request->uri);
        $this->assertSame(['status' => 'CONFIRMED'], $request->body);
    }

    public function testUpdatingAClosedReceiptSendsTheIdInTheBodyAsWell(): void
    {
        $this->transport->queue(Factory::objectResponse(['id' => 9]));

        $this->client->updateClosedReceipt(9, OrderStatus::Completed, 'A-1', 'F1', true);

        $request = $this->transport->lastRequest();

        $this->assertSame('PATCH', $request->method);
        $this->assertSame('https://integrations.clopos.com/open-api/v2/receipts/9', $request->uri);

        // Bu endpoint digərlərindən fərqlidir: `id` həm URL-də, həm body-də gedir.
        $this->assertSame([
            'id' => 9,
            'order_status' => 'COMPLETED',
            'order_number' => 'A-1',
            'fiscal_id' => 'F1',
            'lock' => true,
        ], $request->body);
    }

    public function testClosingAReceiptSendsThePaymentsAndTheClosingTime(): void
    {
        $this->transport->queue(Factory::objectResponse(['id' => 9]));

        $this->client->closeReceipt(
            9,
            'b1e1-uuid',
            [new ReceiptPayment(1, 'Cash', '10.50')],
            '2026-09-18T10:30:45+00:00',
        );

        $request = $this->transport->lastRequest();

        $this->assertSame('https://integrations.clopos.com/open-api/v2/receipts/9/close', $request->uri);
        $this->assertSame([
            'id' => 9,
            'cid' => 'b1e1-uuid',
            'payment_methods' => [['id' => 1, 'name' => 'Cash', 'amount' => '10.50']],
            'closed_at' => '2026-09-18T10:30:45+00:00',
        ], $request->body);
    }

    public function testTheDefaultClosingTimeIsAnEmptyString(): void
    {
        $this->transport->queue(Factory::objectResponse(['id' => 9]));

        $this->client->closeReceipt(9, 'b1e1-uuid', [new ReceiptPayment(1, 'Cash', '1')]);

        // Python modelində `closed_at` default-u boş sətirdir — "indi" mənasında.
        $this->assertSame('', $this->body()['closed_at']);
    }

    public function testReceiptDatesAcceptDateTimeObjects(): void
    {
        $this->transport->queue(Factory::listResponse());

        $this->client->listReceipts(
            dateFrom: new DateTimeImmutable('2026-09-01 00:00:00', new DateTimeZone('UTC')),
            dateTo: '2026-09-18T00:00:00+00:00',
        );

        $this->assertSame([
            'date_from' => '2026-09-01T00:00:00+00:00',
            'date_to' => '2026-09-18T00:00:00+00:00',
        ], $this->query());
    }

    public function testStockOperationsHangOffTheReceipt(): void
    {
        $this->transport->queue(Factory::listResponse());

        $this->client->listReceiptStockOperations(9);

        $this->assertSame(
            'https://integrations.clopos.com/open-api/v2/receipts/9/stock-operations',
            $this->transport->lastRequest()->uri,
        );
    }

    public function testEveryListEndpointHasItsOwnPath(): void
    {
        $paths = [];

        foreach ([
            'listVenues' => 'venues',
            'listUsers' => 'users',
            'listCustomers' => 'customers',
            'listCustomerGroups' => 'customer-groups',
            'listCategories' => 'categories',
            'listStations' => 'stations',
            'listProducts' => 'products',
            'listSaleTypes' => 'sale-types',
            'listPaymentMethods' => 'payment-methods',
            'listOrders' => 'orders',
            'listReceipts' => 'receipts',
            'listPriceLists' => 'price-lists',
            'listPriceListPrices' => 'price-lists/prices',
        ] as $method => $path) {
            $this->transport->queue(Factory::listResponse());
            $this->client->{$method}();

            $paths[$method] = $this->transport->lastRequest()->uri;
            $this->assertSame('https://integrations.clopos.com/open-api/v2/' . $path, $paths[$method]);
        }

        $this->assertCount(13, array_unique($paths));
    }
}
