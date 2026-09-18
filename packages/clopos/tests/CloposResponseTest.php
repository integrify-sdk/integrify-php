<?php

declare(strict_types=1);

namespace Integrify\Clopos\Tests;

use Integrify\Clopos\CloposClient;
use Integrify\Clopos\Dto\Response\Category;
use Integrify\Clopos\Dto\Response\OrderPayload;
use Integrify\Clopos\Dto\Response\OrderProduct;
use Integrify\Clopos\Dto\Response\OrderProductMeta;
use Integrify\Clopos\Dto\Response\Product;
use Integrify\Clopos\Enum\CategoryType;
use Integrify\Clopos\Enum\DiscountType;
use Integrify\Clopos\Enum\OrderStatus;
use Integrify\Clopos\Enum\ProductType;
use Integrify\Clopos\Exception\RequestRejected;
use Integrify\Exception\ValidationFailed;
use Integrify\Http\RecordingTransport;
use Integrify\Response;
use PHPUnit\Framework\TestCase;

/**
 * Məftildən nə gəlir.
 */
final class CloposResponseTest extends TestCase
{
    private RecordingTransport $transport;

    private CloposClient $client;

    protected function setUp(): void
    {
        $this->transport = new RecordingTransport();
        $this->client = Factory::client($this->transport);
    }

    public function testAListResponseCarriesItsEnvelopeMeta(): void
    {
        $this->transport->queue(Factory::listResponse([
            ['id' => 1, 'name' => 'Main'],
            ['id' => 2, 'name' => 'Second'],
        ], total: 57));

        $page = $this->client->listVenues();

        $this->assertSame(57, $page->total);
        $this->assertSame(['id', 'created_at'], $page->sorts);
        $this->assertSame('OK', $page->message);
        $this->assertSame(12, $page->time);
        $this->assertSame('2026-09-18T10:30:45+00:00', $page->timestamp);
        $this->assertSame(1789727445, $page->unix);
    }

    public function testAPageBehavesLikeAList(): void
    {
        $this->transport->queue(Factory::listResponse([
            ['id' => 1, 'name' => 'Main'],
            ['id' => 2, 'name' => 'Second'],
        ], total: 57));

        $page = $this->client->listVenues();

        // Çağıran zərfi açmır: `Page` özü gəzilir və sayılır.
        $this->assertCount(2, $page);
        $this->assertFalse($page->isEmpty());
        $this->assertSame('Main', $page->first()?->name);

        $names = [];

        foreach ($page as $venue) {
            $names[] = $venue->name;
        }

        $this->assertSame(['Main', 'Second'], $names);
    }

    public function testAnEmptyPageIsNotAnError(): void
    {
        $this->transport->queue(Factory::listResponse([]));

        $page = $this->client->listVenues();

        $this->assertTrue($page->isEmpty());
        $this->assertNull($page->first());
        $this->assertSame(0, $page->total);
    }

    public function testASingleObjectIsUnwrappedFromTheEnvelope(): void
    {
        $this->transport->queue(Factory::objectResponse([
            'id' => 5,
            'username' => 'ali',
            'first_name' => 'Əli',
            'last_name' => 'Əliyev',
            'status' => true,
        ]));

        $user = $this->client->getUser(5);

        $this->assertSame(5, $user->id);
        $this->assertSame('ali', $user->username);
        $this->assertSame('Əli', $user->firstName);
        $this->assertTrue($user->status);
    }

    public function testAMissingDataKeyIsAValidationFailure(): void
    {
        $this->transport->queue(Response::json(['success' => true, 'time' => 1]));

        $this->expectException(ValidationFailed::class);

        $this->client->getUser(5);
    }

    public function testAListEndpointRefusesAnObjectUnderData(): void
    {
        $this->transport->queue(Response::json(['success' => true, 'data' => ['id' => 1]]));

        $this->expectException(ValidationFailed::class);

        $this->client->listVenues();
    }

    public function testAmountsArriveAsNumbersAndAreKeptAsStrings(): void
    {
        $this->transport->queue(Factory::objectResponse([
            'id' => 1,
            'name' => 'Çay',
            'price' => 2.5,
            'cost_price' => 1,
            'total_cost' => '3.00',
        ]));

        $product = $this->client->getProduct(1);

        // `float`-a çevirmək qəpik dəqiqliyini itirərdi.
        $this->assertSame('2.5', $product->price);
        $this->assertSame('1', $product->costPrice);
        $this->assertSame('3.00', $product->totalCost);
    }

    public function testUnderscorePrefixedTreeBoundariesAreMapped(): void
    {
        $this->transport->queue(Factory::objectResponse([
            'id' => 3,
            'name' => 'İçkilər',
            'type' => 'PRODUCT',
            '_lft' => 4,
            '_rgt' => 9,
            'depth' => 1,
        ]));

        $category = $this->client->getCategory(3);

        $this->assertSame(4, $category->lft);
        $this->assertSame(9, $category->rgt);
        $this->assertSame(CategoryType::Product, $category->type());
    }

    public function testCategoriesNestTheirChildren(): void
    {
        $this->transport->queue(Factory::objectResponse([
            'id' => 1,
            'name' => 'Menyu',
            'children' => [
                ['id' => 2, 'name' => 'İçkilər', 'children' => [['id' => 3, 'name' => 'Çay']]],
            ],
        ]));

        $category = $this->client->getCategory(1, includeChildren: true);

        $this->assertIsArray($category->children);
        $child = $category->children[0];
        $this->assertInstanceOf(Category::class, $child);
        $this->assertIsArray($child->children);
        $this->assertSame('Çay', $child->children[0]->name);
    }

    public function testAProductRecipeIsAListOfProducts(): void
    {
        $this->transport->queue(Factory::objectResponse([
            'id' => 1,
            'name' => 'Dürüm',
            'type' => 'DISH',
            'recipe' => [['id' => 7, 'name' => 'Lavaş', 'type' => 'INGREDIENT']],
            'modificator_groups' => [[
                'id' => 4,
                'name' => 'Acılıq',
                'type' => 1,
                'modifiers' => [['id' => 9, 'name' => 'Acılı', 'price' => 0.5]],
            ]],
        ]));

        $product = $this->client->getProduct(1);

        $this->assertSame(ProductType::Dish, $product->type());
        $this->assertIsArray($product->recipe);
        $this->assertInstanceOf(Product::class, $product->recipe[0]);
        $this->assertSame(ProductType::Ingredient, $product->recipe[0]->type());

        $this->assertIsArray($product->modificatorGroups);
        $modifiers = $product->modificatorGroups[0]->modifiers;
        $this->assertIsArray($modifiers);
        $this->assertSame('0.5', $modifiers[0]->price);
    }

    public function testTheReceiptTotalCostIsTheOnlyCamelCaseField(): void
    {
        $this->transport->queue(Factory::objectResponse([
            'id' => 9,
            'totalCost' => '42.00',
            'total_discount' => '2.00',
            'order_status' => 'COMPLETED',
            'discount_type' => 1,
            'i_tax' => '0.18',
            'e_tax' => '0.00',
            'payment_methods' => [['id' => 1, 'name' => 'Nağd', 'amount' => 42]],
        ]));

        $receipt = $this->client->getReceipt(9);

        $this->assertSame('42.00', $receipt->totalCost);
        $this->assertSame('2.00', $receipt->totalDiscount);
        $this->assertSame(OrderStatus::Completed, $receipt->orderStatus());
        $this->assertSame(DiscountType::Percentage, $receipt->discountType());
        $this->assertSame('0.18', $receipt->includedTax);
        $this->assertSame('0.00', $receipt->excludedTax);
        $this->assertIsArray($receipt->paymentMethods);
        $this->assertSame('42', $receipt->paymentMethods[0]->amount);
    }

    public function testAnUnknownEnumValueDoesNotBreakValidation(): void
    {
        $this->transport->queue(Factory::objectResponse([
            'id' => 4,
            'status' => 'TELEPORTED',
        ]));

        $order = $this->client->getOrder(4);

        // Servis yeni bir vəziyyət əlavə edərsə cavab yenə də oxunur.
        $this->assertSame('TELEPORTED', $order->status);
        $this->assertNull($order->status());
    }

    public function testAnOrderPayloadIsFullyTyped(): void
    {
        $this->transport->queue(Factory::objectResponse([
            'id' => 4,
            'status' => 'NEW',
            'payload' => [
                'service' => ['sale_type_id' => 2, 'sale_type_name' => 'Delivery', 'venue_id' => 1],
                'customer' => ['id' => 9, 'name' => 'Rashid', 'customer_discount_type' => 1],
                'products' => [[
                    'product_id' => 1,
                    'count' => 2,
                    'meta' => ['price' => 8.1, 'order_product' => ['count' => 2, 'product' => []]],
                ]],
            ],
            'line_items' => [['id' => 1, 'product_id' => 1, 'quantity' => 2, 'unit_price' => 8.1]],
        ]));

        $order = $this->client->getOrder(4);

        $this->assertSame(OrderStatus::New, $order->status());

        $payload = $order->payload;
        $this->assertInstanceOf(OrderPayload::class, $payload);
        $this->assertSame('Delivery', $payload->service?->saleTypeName);
        $this->assertSame(DiscountType::Percentage, $payload->customer?->customerDiscountType());

        $products = $payload->products;
        $this->assertIsArray($products);

        $product = $products[0];
        $this->assertInstanceOf(OrderProduct::class, $product);

        $meta = $product->meta;
        $this->assertInstanceOf(OrderProductMeta::class, $meta);
        $this->assertSame('8.1', $meta->price);

        // Laravel yüklənməmiş əlaqəni `[]` yazır; bu, bütün field-ləri `null` olan
        // `Product`-a çevrilir — `[]`-də məlumat yoxdur, ona görə itən də yoxdur.
        $this->assertNull($meta->orderProduct?->product?->id);

        $this->assertIsArray($order->lineItems);
        $this->assertSame('8.1', $order->lineItems[0]->unitPrice);
    }

    public function testAnErrorResponseBecomesARejection(): void
    {
        $this->transport->queue(Factory::errorResponse());

        try {
            $this->client->getUser(1000);
            $this->fail('Expected a RequestRejected.');
        } catch (RequestRejected $rejection) {
            $this->assertCount(1, $rejection->errors);
            $this->assertSame('NotFoundHttpException', $rejection->errors[0]->exception);
            $this->assertSame('server_side', $rejection->errors[0]->type);
            $this->assertSame(404, $rejection->errors[0]->httpCode);
            $this->assertSame(404, $rejection->getCode());
            $this->assertStringContainsString('No query results', $rejection->getMessage());
            $this->assertSame(404, $rejection->failure->response?->status);
        }
    }

    public function testEveryErrorMessageIsReachable(): void
    {
        $this->transport->queue(Response::json([
            'success' => false,
            'message' => 'Validation failed',
            'error' => [
                ['message' => 'name is required'],
                ['message' => 'phone must be unique'],
            ],
        ], 422));

        try {
            $this->client->createCustomer('');
            $this->fail('Expected a RequestRejected.');
        } catch (RequestRejected $rejection) {
            $this->assertSame(['name is required', 'phone must be unique'], $rejection->messages());
            $this->assertSame('Validation failed', $rejection->summary);
        }
    }

    public function testANonJsonErrorStillRejects(): void
    {
        $this->transport->queue(new Response(502, [], '<html>Bad gateway</html>'));

        try {
            $this->client->listVenues();
            $this->fail('Expected a RequestRejected.');
        } catch (RequestRejected $rejection) {
            // Body oxuna bilmir, lakin status kodu artıq "uğursuz" deməkdir.
            $this->assertSame([], $rejection->errors);
            $this->assertNull($rejection->summary);
            $this->assertSame(502, $rejection->getCode());
        }
    }
}
