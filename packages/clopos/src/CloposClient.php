<?php

declare(strict_types=1);

namespace Integrify\Clopos;

use DateTimeInterface;
use Integrify\Client;
use Integrify\Clopos\Dto\Request\CustomerFilter;
use Integrify\Clopos\Dto\Request\NewOrder;
use Integrify\Clopos\Dto\Request\ProductFilter;
use Integrify\Clopos\Dto\Request\ReceiptPayment;
use Integrify\Clopos\Dto\Request\StopListFilter;
use Integrify\Clopos\Dto\Response\AuthToken;
use Integrify\Clopos\Dto\Response\Category;
use Integrify\Clopos\Dto\Response\Customer;
use Integrify\Clopos\Dto\Response\CustomerGroup;
use Integrify\Clopos\Dto\Response\Order;
use Integrify\Clopos\Dto\Response\PaymentMethod;
use Integrify\Clopos\Dto\Response\PriceList;
use Integrify\Clopos\Dto\Response\PriceListPrice;
use Integrify\Clopos\Dto\Response\Product;
use Integrify\Clopos\Dto\Response\Receipt;
use Integrify\Clopos\Dto\Response\ReceiptStockOperation;
use Integrify\Clopos\Dto\Response\SaleType;
use Integrify\Clopos\Dto\Response\Station;
use Integrify\Clopos\Dto\Response\StopListEntry;
use Integrify\Clopos\Dto\Response\User;
use Integrify\Clopos\Dto\Response\Venue;
use Integrify\Clopos\Enum\CategoryType;
use Integrify\Clopos\Enum\CustomerRelation;
use Integrify\Clopos\Enum\Gender;
use Integrify\Clopos\Enum\OrderRelation;
use Integrify\Clopos\Enum\OrderStatus;
use Integrify\Clopos\Enum\ProductRelation;
use Integrify\Clopos\Exception\RequestRejected;
use Integrify\Dto\Data;
use Integrify\Exception\MissingConfiguration;
use Integrify\Exception\RequestFailed;
use Integrify\Exception\ValidationFailed;
use Integrify\Http\HttpTransport;
use Integrify\Http\Request;
use Integrify\Http\Transport;
use Integrify\Response;
use stdClass;

/**
 * Clopos Open API v2 — POS sistemi ilə inteqrasiya.
 *
 * Bu, kitabxanadakı yeganə **ödəniş şlüzü olmayan** inteqrasiyadır: burada sifariş
 * qəbul edilir, menyu oxunur, çek bağlanır. Pul hərəkəti POS-un öz içindədir.
 *
 * ```php
 * $client = new CloposClient(CloposConfig::fromEnvironment());
 *
 * $client->authenticate();                       // token bir saat yaşayır
 *
 * foreach ($client->listProducts() as $product) {
 *     echo $product->name, ' — ', $product->price, PHP_EOL;
 * }
 * ```
 *
 * ## Token
 *
 * Token klientin **içində** saxlanılır və hər sorğuya `x-token` header-i kimi əlavə
 * olunur; Python-dakı kimi onu hər çağırışda əl ilə ötürmək lazım deyil. Bir saat
 * yaşadığı üçün cache-ləmək istəsəniz `token()` və `useToken()` açıqdır:
 *
 * ```php
 * $client = new CloposClient($config, token: $cache->get('clopos'));
 *
 * if ($client->token() === null) {
 *     $cache->set('clopos', $client->authenticate()->token, ttl: 3500);
 * }
 * ```
 *
 * ## Səhifələmə
 *
 * Siyahı qaytaran metodlar `Page` qaytarır. `Page` özü siyahı kimi davranır —
 * `foreach` ilə birbaşa gəzilir — lakin `total` da onun üzərindədir.
 *
 * ## Xətalar
 *
 * Clopos xətanı HTTP status kodu ilə bildirir, ona görə uğursuzluq həmişə
 * `RequestRejected` kimi qalxır; servisin izahı exception-un `errors` field-indədir.
 */
final class CloposClient extends Client
{
    private ?string $token;

    /**
     * @param CloposConfig $config Klientin konfiqurasiyası.
     * @param Transport|null $transport Sorğu nəqliyyatı. Verilməsə, tətbiqin PSR-18
     *     klienti tapılır.
     * @param string|null $token Əvvəlki sessiyadan qalan token. Verilməsə,
     *     `authenticate()` çağırılmalıdır.
     */
    public function __construct(
        private readonly CloposConfig $config,
        ?Transport $transport = null,
        ?string $token = null,
    ) {
        parent::__construct($transport ?? new HttpTransport(), $config->baseUrl);

        $this->token = $token;
    }

    /**
     * Hazırkı token, yoxdursa `null`.
     */
    public function token(): ?string
    {
        return $this->token;
    }

    /**
     * Xaricdən (məs. cache-dən) gələn token-i quraşdırır.
     */
    public function useToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Hər sorğuya əlavə olunan header-lər.
     *
     * `x-brand` **lazım deyil**: v2-də brend, filial və inteqrator token-in içində
     * kodlanıb. `x-venue` isə yalnız konfiqurasiyada varsa göndərilir və token-dəki
     * filialı həmin sorğu üçün əvəz edir.
     *
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];

        if ($this->token !== null) {
            $headers['x-token'] = $this->token;
        }

        if ($this->config->venueId !== null) {
            $headers['x-venue'] = $this->config->venueId;
        }

        return $headers;
    }

    /**
     * Hər sorğunu `RequestRejected`-ə bükür.
     *
     * Clopos uğursuzluğu HTTP status kodu ilə bildirir və body-də struktur verir;
     * `RequestFailed`-in mesajı isə yalnız status kodunu göstərir. Bükməni bir yerdə
     * etmək hər metodda `try`/`catch` yazmaqdan yaxşıdır.
     *
     * @param array<array-key, mixed>|Data|stdClass|null $body
     * @param array<string, scalar> $query
     * @param array<string, string> $headers
     *
     * @throws RequestRejected
     */
    private function attempt(
        string $method,
        string $path,
        array|Data|stdClass|null $body = null,
        array $query = [],
        array $headers = [],
    ): Response {
        try {
            return parent::send($method, $path, $body, $query, $headers);
        } catch (RequestFailed $failure) {
            throw RequestRejected::from($failure);
        }
    }

    /**
     * @param array<string, scalar> $query
     * @param array<string, string> $headers
     *
     * @throws RequestRejected
     */
    protected function get(string $path, array $query = [], array $headers = []): Response
    {
        return $this->attempt('GET', $path, query: $query, headers: $headers);
    }

    /**
     * @param array<array-key, mixed>|Data|stdClass|null $body
     * @param array<string, string> $headers
     *
     * @throws RequestRejected
     */
    protected function post(string $path, array|Data|stdClass|null $body = null, array $headers = []): Response
    {
        return $this->attempt('POST', $path, body: $body, headers: $headers);
    }

    /**
     * @param array<array-key, mixed>|Data|stdClass|null $body
     * @param array<string, string> $headers
     *
     * @throws RequestRejected
     */
    protected function put(string $path, array|Data|stdClass|null $body = null, array $headers = []): Response
    {
        return $this->attempt('PUT', $path, body: $body, headers: $headers);
    }

    /**
     * `PATCH` — `Client` bunu ayrıca metod kimi vermir, yalnız `receipts/{id}` üçün lazımdır.
     *
     * @param array<array-key, mixed>|Data|stdClass|null $body
     *
     * @throws RequestRejected
     */
    private function patch(string $path, array|Data|stdClass|null $body = null): Response
    {
        return $this->attempt('PATCH', $path, body: $body);
    }

    // --------------------------------------------------------------------------------------- //
    // Auth                                                                                     //
    // --------------------------------------------------------------------------------------- //

    /**
     * Token alır və klientdə saxlayır.
     *
     * **POST** `/auth`
     *
     * Token bir saat yaşayır. Bu, `x-token` header-i **göndərmədən** atılan yeganə
     * sorğudur — köhnə token yeni token istəyərkən mənasızdır.
     *
     * @throws MissingConfiguration Dörd açardan biri yoxdursa.
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function authenticate(): AuthToken
    {
        $request = new Request(
            method: 'POST',
            uri: $this->uri(Endpoint::Auth->value),
            body: $this->config->credentials(),
            headers: ['Content-Type' => 'application/json'],
        );

        try {
            $response = $this->transport->send($request);
        } catch (RequestFailed $failure) {
            throw RequestRejected::from($failure);
        }

        $token = $response->to(AuthToken::class);
        $this->token = $token->token;

        return $token;
    }

    // --------------------------------------------------------------------------------------- //
    // Venues, users                                                                            //
    // --------------------------------------------------------------------------------------- //

    /**
     * Brendin filialları.
     *
     * **GET** `/venues`
     *
     * @param int|null $page Səhifə nömrəsi (1-dən başlayır).
     * @param int|null $limit Səhifədəki element sayı.
     *
     * @return Page<Venue>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listVenues(?int $page = null, ?int $limit = null): Page
    {
        return self::page(
            $this->get(Endpoint::Venues->value, self::pagination($page, $limit)),
            Venue::class,
        );
    }

    /**
     * POS istifadəçiləri.
     *
     * **GET** `/users`
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     *
     * @return Page<User>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listUsers(?int $page = null, ?int $limit = null): Page
    {
        return self::page(
            $this->get(Endpoint::Users->value, self::pagination($page, $limit)),
            User::class,
        );
    }

    /**
     * Bir istifadəçi.
     *
     * **GET** `/users/{id}`
     *
     * @param int $id İstifadəçinin IDsi.
     *
     * @throws RequestRejected Belə istifadəçi yoxdursa (HTTP 404).
     * @throws ValidationFailed
     */
    public function getUser(int $id): User
    {
        return self::object($this->get($this->uri(Endpoint::User->value, ['id' => $id])), User::class);
    }

    // --------------------------------------------------------------------------------------- //
    // Customers                                                                                //
    // --------------------------------------------------------------------------------------- //

    /**
     * Müştərilər.
     *
     * **GET** `/customers`
     *
     * ```php
     * $client->listCustomers(
     *     with: [CustomerRelation::Group, CustomerRelation::Balance],
     *     filters: [new CustomerFilter(CustomerFilterField::Name, 'John Doe')],
     * );
     * ```
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     * @param list<CustomerRelation>|null $with Əlavə yüklənəcək əlaqələr.
     * @param list<CustomerFilter>|null $filters Axtarış filtrləri.
     *
     * @return Page<Customer>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listCustomers(
        ?int $page = null,
        ?int $limit = null,
        ?array $with = null,
        ?array $filters = null,
    ): Page {
        $query = self::pagination($page, $limit);

        foreach ($with ?? [] as $index => $relation) {
            $query['with[' . $index . ']'] = $relation->value;
        }

        foreach ($filters ?? [] as $index => $filter) {
            $query['filters[' . $index . '][0]'] = $filter->by->value;
            $query['filters[' . $index . '][1]'] = $filter->value;
        }

        return self::page($this->get(Endpoint::Customers->value, $query), Customer::class);
    }

    /**
     * Bir müştəri.
     *
     * **GET** `/customers/{id}`
     *
     * @param int $id Müştərinin IDsi.
     *
     * @throws RequestRejected Belə müştəri yoxdursa (HTTP 404).
     * @throws ValidationFailed
     */
    public function getCustomer(int $id): Customer
    {
        return self::object(
            $this->get($this->uri(Endpoint::Customer->value, ['id' => $id])),
            Customer::class,
        );
    }

    /**
     * Yeni müştəri yaradır.
     *
     * **POST** `/customers`
     *
     * @param string $name Müştərinin adı — yeganə məcburi field.
     * @param string|null $email Email ünvanı.
     * @param string|null $phone Telefon nömrəsi. Brend daxilində unikal olmalıdır.
     * @param string|null $code Müştərinin kodu. Brend daxilində unikal olmalıdır.
     * @param string|null $cid POS identifikatoru.
     * @param string|null $description Qeyd.
     * @param int|null $groupId Müştəri qrupunun IDsi.
     * @param Gender|null $gender Cinsi.
     * @param string|DateTimeInterface|null $dateOfBirth Doğum tarixi (`YYYY-MM-DD`).
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function createCustomer(
        string $name,
        ?string $email = null,
        ?string $phone = null,
        ?string $code = null,
        ?string $cid = null,
        ?string $description = null,
        ?int $groupId = null,
        ?Gender $gender = null,
        string|DateTimeInterface|null $dateOfBirth = null,
    ): Customer {
        $body = array_filter([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'code' => $code,
            'cid' => $cid,
            'description' => $description,
            'group_id' => $groupId,
            'gender' => $gender?->value,
            'date_of_birth' => self::date($dateOfBirth),
        ], static fn (mixed $value): bool => $value !== null);

        return self::object($this->post(Endpoint::Customers->value, $body), Customer::class);
    }

    /**
     * Müştəri qrupları.
     *
     * **GET** `/customer-groups`
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     *
     * @return Page<CustomerGroup>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listCustomerGroups(?int $page = null, ?int $limit = null): Page
    {
        return self::page(
            $this->get(Endpoint::CustomerGroups->value, self::pagination($page, $limit)),
            CustomerGroup::class,
        );
    }

    // --------------------------------------------------------------------------------------- //
    // Menu: categories, stations, products                                                     //
    // --------------------------------------------------------------------------------------- //

    /**
     * Menyu kateqoriyaları.
     *
     * **GET** `/categories`
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     * @param int|null $parentId Yalnız bu kateqoriyanın altındakılar.
     * @param CategoryType|null $type Kateqoriyanın növü.
     * @param bool|null $includeChildren Alt kateqoriyalar da qaytarılsınmı.
     * @param bool|null $includeInactive Deaktiv kateqoriyalar da qaytarılsınmı.
     *
     * @return Page<Category>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listCategories(
        ?int $page = null,
        ?int $limit = null,
        ?int $parentId = null,
        ?CategoryType $type = null,
        ?bool $includeChildren = null,
        ?bool $includeInactive = null,
    ): Page {
        $query = self::pagination($page, $limit);

        if ($parentId !== null) {
            $query['parent_id'] = $parentId;
        }

        if ($type !== null) {
            $query['type'] = $type->value;
        }

        if ($includeChildren !== null) {
            $query['include_children'] = self::flag($includeChildren);
        }

        if ($includeInactive !== null) {
            $query['include_inactive'] = self::flag($includeInactive);
        }

        return self::page($this->get(Endpoint::Categories->value, $query), Category::class);
    }

    /**
     * Bir kateqoriya.
     *
     * **GET** `/categories/{id}`
     *
     * @param int $id Kateqoriyanın IDsi.
     * @param bool|null $includeChildren Alt kateqoriyalar da qaytarılsınmı.
     *
     * @throws RequestRejected Belə kateqoriya yoxdursa (HTTP 404).
     * @throws ValidationFailed
     */
    public function getCategory(int $id, ?bool $includeChildren = null): Category
    {
        $query = $includeChildren === null ? [] : ['include_children' => self::flag($includeChildren)];

        return self::object(
            $this->get($this->uri(Endpoint::Category->value, ['id' => $id]), $query),
            Category::class,
        );
    }

    /**
     * Stansiyalar.
     *
     * **GET** `/stations`
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     * @param int|null $status `1` aktiv, `0` deaktiv.
     * @param bool|null $canPrint Yalnız printerə yönləndirilə bilənlər.
     *
     * @return Page<Station>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listStations(
        ?int $page = null,
        ?int $limit = null,
        ?int $status = null,
        ?bool $canPrint = null,
    ): Page {
        $query = self::pagination($page, $limit);

        if ($status !== null) {
            $query['status'] = $status;
        }

        if ($canPrint !== null) {
            $query['can_print'] = self::flag($canPrint);
        }

        return self::page($this->get(Endpoint::Stations->value, $query), Station::class);
    }

    /**
     * Bir stansiya.
     *
     * **GET** `/stations/{id}`
     *
     * @param int $id Stansiyanın IDsi.
     *
     * @throws RequestRejected Belə stansiya yoxdursa (HTTP 404).
     * @throws ValidationFailed
     */
    public function getStation(int $id): Station
    {
        return self::object(
            $this->get($this->uri(Endpoint::Station->value, ['id' => $id])),
            Station::class,
        );
    }

    /**
     * Məhsullar.
     *
     * **GET** `/products`
     *
     * ```php
     * $client->listProducts(
     *     selects: ['id', 'name'],
     *     filters: new ProductFilter(giftable: true, type: [ProductType::Dish]),
     * );
     * ```
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     * @param list<string>|string|null $selects Yalnız sadalanan field-lər qaytarılır.
     * @param ProductFilter|null $filters Filtrlər.
     *
     * @return Page<Product>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listProducts(
        ?int $page = null,
        ?int $limit = null,
        array|string|null $selects = null,
        ?ProductFilter $filters = null,
    ): Page {
        $query = self::pagination($page, $limit);

        if ($selects !== null) {
            $query['selects[]'] = is_string($selects) ? $selects : implode(',', $selects);
        }

        foreach ($filters?->toArray() ?? [] as $name => $pair) {
            $query['filters[' . $name . '][0]'] = $pair[0];

            if (is_array($pair[1])) {
                foreach (array_values($pair[1]) as $index => $value) {
                    $query['filters[' . $name . '][1][' . $index . ']'] = $value;
                }

                continue;
            }

            $query['filters[' . $name . '][1]'] = $pair[1];
        }

        return self::page($this->get(Endpoint::Products->value, $query), Product::class);
    }

    /**
     * Bir məhsul.
     *
     * **GET** `/products/{id}`
     *
     * @param int $id Məhsulun IDsi.
     * @param list<ProductRelation>|null $with Əlavə yüklənəcək əlaqələr.
     *
     * @throws RequestRejected Belə məhsul yoxdursa (HTTP 404).
     * @throws ValidationFailed
     */
    public function getProduct(int $id, ?array $with = null): Product
    {
        $query = [];

        foreach ($with ?? [] as $index => $relation) {
            $query['with[' . $index . ']'] = $relation->value;
        }

        return self::object(
            $this->get($this->uri(Endpoint::Product->value, ['id' => $id]), $query),
            Product::class,
        );
    }

    /**
     * Stop-list — qalığı azalmış məhsullar.
     *
     * **GET** `/products/stop-list`
     *
     * ```php
     * $client->getStopList(new StopListFilter(StopListFilterField::Limit, from: 0, to: 10));
     * ```
     *
     * @param StopListFilter ...$filters Aralıq filtrləri.
     *
     * @return Page<StopListEntry>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function getStopList(StopListFilter ...$filters): Page
    {
        $query = [];

        foreach (array_values($filters) as $index => $filter) {
            $query['filters[' . $index . '][0]'] = $filter->by->value;
            $query['filters[' . $index . '][1][0]'] = $filter->from;

            if ($filter->to !== null) {
                $query['filters[' . $index . '][1][1]'] = $filter->to;
            }
        }

        return self::page($this->get(Endpoint::StopList->value, $query), StopListEntry::class);
    }

    // --------------------------------------------------------------------------------------- //
    // Sales                                                                                    //
    // --------------------------------------------------------------------------------------- //

    /**
     * Satış növləri.
     *
     * **GET** `/sale-types`
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     *
     * @return Page<SaleType>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listSaleTypes(?int $page = null, ?int $limit = null): Page
    {
        return self::page(
            $this->get(Endpoint::SaleTypes->value, self::pagination($page, $limit)),
            SaleType::class,
        );
    }

    /**
     * Ödəniş metodları.
     *
     * **GET** `/payment-methods`
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     *
     * @return Page<PaymentMethod>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listPaymentMethods(?int $page = null, ?int $limit = null): Page
    {
        return self::page(
            $this->get(Endpoint::PaymentMethods->value, self::pagination($page, $limit)),
            PaymentMethod::class,
        );
    }

    // --------------------------------------------------------------------------------------- //
    // Orders                                                                                   //
    // --------------------------------------------------------------------------------------- //

    /**
     * Sifarişlər.
     *
     * **GET** `/orders`
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     * @param OrderStatus|null $status Yalnız bu vəziyyətdəkilər.
     *
     * @return Page<Order>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listOrders(?int $page = null, ?int $limit = null, ?OrderStatus $status = null): Page
    {
        $query = self::pagination($page, $limit);

        if ($status !== null) {
            $query['status'] = $status->value;
        }

        return self::page($this->get(Endpoint::Orders->value, $query), Order::class);
    }

    /**
     * Bir sifariş.
     *
     * **GET** `/orders/{id}`
     *
     * @param int $id Sifarişin IDsi.
     * @param OrderRelation|null $with Əlavə yüklənəcək əlaqə — Clopos burada **bir** dəyər qəbul edir.
     *
     * @throws RequestRejected Belə sifariş yoxdursa (HTTP 404).
     * @throws ValidationFailed
     */
    public function getOrder(int $id, ?OrderRelation $with = null): Order
    {
        $query = $with === null ? [] : ['with' => $with->value];

        return self::object(
            $this->get($this->uri(Endpoint::Order->value, ['id' => $id]), $query),
            Order::class,
        );
    }

    /**
     * Yeni sifariş yaradır.
     *
     * **POST** `/orders`
     *
     * @param int $customerId Sifarişi verən müştərinin IDsi.
     * @param NewOrder $order Sifarişin tərkibi.
     * @param array<string, mixed>|null $meta Sifarişin əlavə məlumatları (endirim, şərh və s.).
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function createOrder(int $customerId, NewOrder $order, ?array $meta = null): Order
    {
        $body = ['customer_id' => $customerId, 'payload' => $order->toPayload()];

        if ($meta !== null) {
            $body['meta'] = $meta;
        }

        return self::object($this->post(Endpoint::Orders->value, $body), Order::class);
    }

    /**
     * Sifarişin vəziyyətini dəyişir.
     *
     * **PUT** `/orders/{id}`
     *
     * `id` yalnız URL-dədir — body-də getmir.
     *
     * @param int $id Sifarişin IDsi.
     * @param OrderStatus $status Yeni vəziyyət.
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function updateOrderStatus(int $id, OrderStatus $status): Order
    {
        return self::object(
            $this->put($this->uri(Endpoint::Order->value, ['id' => $id]), ['status' => $status->value]),
            Order::class,
        );
    }

    // --------------------------------------------------------------------------------------- //
    // Receipts                                                                                 //
    // --------------------------------------------------------------------------------------- //

    /**
     * Çeklər.
     *
     * **GET** `/receipts`
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     * @param string|null $sortBy Sıralama field-i, məs. `created_at`.
     * @param int|null $sortOrder `1` artan, `-1` azalan.
     * @param string|DateTimeInterface|null $dateFrom Aralığın başlanğıcı (ISO 8601).
     * @param string|DateTimeInterface|null $dateTo Aralığın sonu (ISO 8601).
     *
     * @return Page<Receipt>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listReceipts(
        ?int $page = null,
        ?int $limit = null,
        ?string $sortBy = null,
        ?int $sortOrder = null,
        string|DateTimeInterface|null $dateFrom = null,
        string|DateTimeInterface|null $dateTo = null,
    ): Page {
        $query = self::pagination($page, $limit);

        if ($sortBy !== null) {
            $query['sort_by'] = $sortBy;
        }

        if ($sortOrder !== null) {
            $query['sort_order'] = $sortOrder;
        }

        if ($dateFrom !== null) {
            $query['date_from'] = self::dateTime($dateFrom);
        }

        if ($dateTo !== null) {
            $query['date_to'] = self::dateTime($dateTo);
        }

        return self::page($this->get(Endpoint::Receipts->value, $query), Receipt::class);
    }

    /**
     * Bir çek.
     *
     * **GET** `/receipts/{id}`
     *
     * @param int $id Çekin IDsi.
     *
     * @throws RequestRejected Belə çek yoxdursa (HTTP 404).
     * @throws ValidationFailed
     */
    public function getReceipt(int $id): Receipt
    {
        return self::object(
            $this->get($this->uri(Endpoint::Receipt->value, ['id' => $id])),
            Receipt::class,
        );
    }

    /**
     * Bağlanmış çekin bir neçə field-ini dəyişir.
     *
     * **PATCH** `/receipts/{id}`
     *
     * > `id` **həm URL-də, həm də body-də** gedir. Bu, digər endpoint-lərdən fərqlidir
     * > (məs. `updateOrderStatus()` onu yalnız URL-də göndərir) və Python
     * > kitabxanasındakı davranışla eynidir.
     *
     * @param int $id Çekin IDsi.
     * @param OrderStatus|null $orderStatus Sifarişin yeni vəziyyəti.
     * @param string|null $orderNumber Sifariş nömrəsi.
     * @param string|null $fiscalId Fiskal identifikator.
     * @param bool|null $lock Çek kilidlənsinmi.
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function updateClosedReceipt(
        int $id,
        ?OrderStatus $orderStatus = null,
        ?string $orderNumber = null,
        ?string $fiscalId = null,
        ?bool $lock = null,
    ): Receipt {
        $body = ['id' => $id];

        if ($orderStatus !== null) {
            $body['order_status'] = $orderStatus->value;
        }

        if ($orderNumber !== null) {
            $body['order_number'] = $orderNumber;
        }

        if ($fiscalId !== null) {
            $body['fiscal_id'] = $fiscalId;
        }

        if ($lock !== null) {
            $body['lock'] = $lock;
        }

        return self::object(
            $this->patch($this->uri(Endpoint::Receipt->value, ['id' => $id]), $body),
            Receipt::class,
        );
    }

    /**
     * Açıq çeki bağlayır.
     *
     * **POST** `/receipts/{id}/close`
     *
     * > `id` burada da həm URL-də, həm body-də gedir.
     *
     * @param int $id Çekin IDsi.
     * @param string $cid Çekin POS identifikatoru (UUID).
     * @param list<ReceiptPayment> $payments Ödəniş sətirləri — cəmi çekin qalığını örtməlidir.
     * @param string|DateTimeInterface $closedAt Bağlanma vaxtı (ISO 8601). Boş sətir "indi" deməkdir.
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function closeReceipt(
        int $id,
        string $cid,
        array $payments,
        string|DateTimeInterface $closedAt = '',
    ): Receipt {
        $body = [
            'id' => $id,
            'cid' => $cid,
            'payment_methods' => array_map(
                static fn (ReceiptPayment $payment): array => $payment->toArray(),
                array_values($payments),
            ),
            'closed_at' => self::dateTime($closedAt),
        ];

        return self::object(
            $this->post($this->uri(Endpoint::ReceiptClose->value, ['id' => $id]), $body),
            Receipt::class,
        );
    }

    /**
     * Çekin yaratdığı anbar hərəkətləri.
     *
     * **GET** `/receipts/{id}/stock-operations`
     *
     * @param int $id Çekin IDsi.
     *
     * @return Page<ReceiptStockOperation>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listReceiptStockOperations(int $id): Page
    {
        return self::page(
            $this->get($this->uri(Endpoint::ReceiptStockOperations->value, ['id' => $id])),
            ReceiptStockOperation::class,
        );
    }

    // --------------------------------------------------------------------------------------- //
    // Price lists                                                                              //
    // --------------------------------------------------------------------------------------- //

    /**
     * Qiymət cədvəlləri.
     *
     * **GET** `/price-lists`
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     *
     * @return Page<PriceList>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listPriceLists(?int $page = null, ?int $limit = null): Page
    {
        return self::page(
            $this->get(Endpoint::PriceLists->value, self::pagination($page, $limit)),
            PriceList::class,
        );
    }

    /**
     * Qiymət cədvəllərindəki qiymətlər.
     *
     * **GET** `/price-lists/prices`
     *
     * @param int|null $page Səhifə nömrəsi.
     * @param int|null $limit Səhifədəki element sayı.
     *
     * @return Page<PriceListPrice>
     *
     * @throws RequestRejected
     * @throws ValidationFailed
     */
    public function listPriceListPrices(?int $page = null, ?int $limit = null): Page
    {
        return self::page(
            $this->get(Endpoint::PriceListPrices->value, self::pagination($page, $limit)),
            PriceListPrice::class,
        );
    }

    // --------------------------------------------------------------------------------------- //
    // Zərfin açılması                                                                          //
    // --------------------------------------------------------------------------------------- //

    /**
     * Siyahı zərfini `Page`-ə çevirir.
     *
     * @template T of Data
     *
     * @param class-string<T> $dto
     *
     * @return Page<T>
     *
     * @throws ValidationFailed `data` yoxdursa və ya siyahı deyilsə.
     */
    private static function page(Response $response, string $dto): Page
    {
        $body = $response->toArray();
        /** @var mixed $data */
        $data = $body['data'] ?? null;

        if (!is_array($data) || !array_is_list($data)) {
            throw new ValidationFailed($dto, ['data' => 'expected a JSON array under "data"']);
        }

        $items = [];

        /** @var mixed $item */
        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new ValidationFailed($dto, [
                    'data[' . $index . ']' => sprintf('expected an object, got %s', get_debug_type($item)),
                ]);
            }

            $items[] = $dto::from($item);
        }

        return new Page(
            items: $items,
            total: self::integer($body['total'] ?? null),
            sorts: self::strings($body['sorts'] ?? null),
            message: is_string($body['message'] ?? null) ? $body['message'] : null,
            time: self::integer($body['time'] ?? null),
            timestamp: is_string($body['timestamp'] ?? null) ? $body['timestamp'] : null,
            unix: self::integer($body['unix'] ?? null),
        );
    }

    /**
     * Tək obyekt zərfini açır.
     *
     * @template T of Data
     *
     * @param class-string<T> $dto
     *
     * @return T
     *
     * @throws ValidationFailed `data` yoxdursa və ya obyekt deyilsə.
     */
    private static function object(Response $response, string $dto): Data
    {
        /** @var mixed $data */
        $data = $response->toArray()['data'] ?? null;

        if (!is_array($data)) {
            throw new ValidationFailed($dto, ['data' => 'expected an object under "data"']);
        }

        return $dto::from($data);
    }

    // --------------------------------------------------------------------------------------- //
    // Kiçik köməkçilər                                                                         //
    // --------------------------------------------------------------------------------------- //

    /**
     * @return array<string, scalar>
     */
    private static function pagination(?int $page, ?int $limit): array
    {
        $query = [];

        if ($page !== null) {
            $query['page'] = $page;
        }

        if ($limit !== null) {
            $query['limit'] = $limit;
        }

        return $query;
    }

    /**
     * Bool query parametri — `1`/`0` yox, `true`/`false`.
     *
     * PHP-nin `http_build_query()` funksiyası `true`-nu `1`, `false`-u isə **boş
     * sətir** kimi yazır; sonuncusu filtri tamamilə sıradan çıxarardı. Python
     * tərəfdəki httpx `true`/`false` göndərir, ona görə burada da belədir.
     */
    private static function flag(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    /**
     * ISO 8601 date-time — Python-un `datetime.isoformat()` qarşılığı.
     */
    private static function dateTime(string|DateTimeInterface $value): string
    {
        return $value instanceof DateTimeInterface ? $value->format('c') : $value;
    }

    /**
     * ISO 8601 tarix (`YYYY-MM-DD`) — Python-un `date.isoformat()` qarşılığı.
     */
    private static function date(string|DateTimeInterface|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
    }

    private static function integer(mixed $value): ?int
    {
        return is_int($value) ? $value : null;
    }

    /**
     * Zərfdəki `sorts` — sətir siyahısı, gözlənilməz elementlər atılır.
     *
     * @return list<string>
     */
    private static function strings(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $strings = [];

        /** @var mixed $item */
        foreach ($value as $item) {
            if (is_string($item)) {
                $strings[] = $item;
            }
        }

        return $strings;
    }
}
