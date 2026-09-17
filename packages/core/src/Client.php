<?php

declare(strict_types=1);

namespace Integrify;

use Integrify\Dto\Data;
use Integrify\Exception\InvalidRequest;
use Integrify\Exception\RequestFailed;
use Integrify\Http\Request;
use Integrify\Http\Transport;
use stdClass;

/**
 * İnteqrasiya klientlərinin baza class-ı.
 *
 * Bu kitabxanada sorğular **adi, tipli metodlardır** — magic dispatch yoxdur.
 * Baza class yalnız ortaq mexanikanı verir: baza url, hər sorğuya əlavə olunan
 * header-lər və `Transport`-a ötürmə.
 *
 * ```php
 * final class MyClient extends Client
 * {
 *     public function pay(int $amount, string $orderId): PaymentResult
 *     {
 *         return $this->post('/pay', ['amount' => $amount, 'order_id' => $orderId])
 *             ->to(PaymentResult::class);
 *     }
 * }
 * ```
 */
abstract class Client
{
    public function __construct(
        protected readonly Transport $transport,
        protected readonly string $baseUrl,
    ) {
    }

    /**
     * Hər sorğuya əlavə olunan header-lər. Alt class-lar genişləndirə bilər.
     *
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    /**
     * @param array<string, string> $headers
     */
    private static function hasHeader(array $headers, string $name): bool
    {
        foreach (array_keys($headers) as $key) {
            if (strcasecmp($key, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Header-ləri **böyük-kiçik hərf fərqi nəzərə alınmadan** birləşdirir.
     *
     * PHP massivində `Content-Type` və `content-type` ayrı açarlardır, HTTP-də isə
     * eyni header. Sadə `[...$a, ...$b]` hər ikisini saxlayır: məftildə sonuncusu
     * qalib gəlir, lakin `Request::$headers` (testlərin baxdığı yer) heç vaxt
     * göndərilməyən bir dəyəri də göstərir.
     *
     * @param array<string, string> ...$sets
     *
     * @return array<string, string>
     */
    protected static function mergeHeaders(array ...$sets): array
    {
        /** @var array<string, string> $merged lowercase ad => dəyər */
        $merged = [];
        /** @var array<string, string> $names lowercase ad => orijinal yazılış */
        $names = [];

        foreach ($sets as $set) {
            foreach ($set as $name => $value) {
                $key = strtolower($name);
                $names[$key] ??= $name;
                $merged[$key] = $value;
            }
        }

        $result = [];

        foreach ($merged as $key => $value) {
            $result[$names[$key]] = $value;
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed>|Data|stdClass|null $body
     * @param array<string, scalar> $query
     * @param array<string, string> $headers
     *
     * @throws RequestFailed
     */
    protected function send(
        string $method,
        string $path,
        array|Data|stdClass|null $body = null,
        array $query = [],
        array $headers = [],
    ): Response {
        $payload = $body instanceof Data ? self::objectBody($body->toArray(skipNull: true)) : $body;
        $merged = self::mergeHeaders($this->defaultHeaders(), $headers);

        // Body-siz sorğuda `Content-Type` mənasızdır və bəzi gateway-lər onu rədd edir.
        // Çağıran özü açıq şəkildə veribsə, toxunmuruq.
        if ($payload === null && !self::hasHeader($headers, 'Content-Type')) {
            foreach (array_keys($merged) as $name) {
                if (strcasecmp($name, 'Content-Type') === 0) {
                    unset($merged[$name]);
                }
            }
        }

        return $this->transport->send(new Request(
            method: $method,
            uri: $this->uri($path),
            body: $payload,
            query: $query,
            headers: $merged,
        ));
    }

    /**
     * @param array<string, scalar> $query
     * @param array<string, string> $headers
     *
     * @throws RequestFailed
     */
    protected function get(string $path, array $query = [], array $headers = []): Response
    {
        return $this->send('GET', $path, query: $query, headers: $headers);
    }

    /**
     * @param array<array-key, mixed>|Data|stdClass|null $body
     * @param array<string, string> $headers
     *
     * @throws RequestFailed
     */
    protected function post(string $path, array|Data|stdClass|null $body = null, array $headers = []): Response
    {
        return $this->send('POST', $path, body: $body, headers: $headers);
    }

    /**
     * @param array<array-key, mixed>|Data|stdClass|null $body
     * @param array<string, string> $headers
     *
     * @throws RequestFailed
     */
    protected function put(string $path, array|Data|stdClass|null $body = null, array $headers = []): Response
    {
        return $this->send('PUT', $path, body: $body, headers: $headers);
    }

    /**
     * @param array<array-key, mixed>|Data|stdClass|null $body
     * @param array<string, string> $headers
     *
     * @throws RequestFailed
     */
    protected function delete(string $path, array|Data|stdClass|null $body = null, array $headers = []): Response
    {
        return $this->send('DELETE', $path, body: $body, headers: $headers);
    }

    /**
     * DTO siyahısını kök səviyyəli JSON array-ə çevirir.
     *
     * Boş siyahı `[]` olaraq qalır və JSON-a `[]` kimi yazılır; bütün field-ləri
     * `null` olan element isə `{}` olur (bax: `objectBody()`).
     *
     * @param iterable<Data> $items
     *
     * @return list<array<string, mixed>|stdClass>
     */
    protected static function listBody(iterable $items): array
    {
        $body = [];

        foreach ($items as $item) {
            $body[] = self::objectBody($item->toArray(skipNull: true));
        }

        return $body;
    }

    /**
     * Massivin JSON-a **obyekt** kimi yazılmasını təmin edir.
     *
     * PHP-də `[]` həm boş obyekt, həm də boş siyahıdır və `json_encode()` onu `[]`
     * kimi yazır. Boş obyekt göndərmək lazım olduqda (məs., filtri olmayan axtarış
     * sorğusu) bu metoddan keçirin.
     *
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>|stdClass
     */
    protected static function objectBody(array $body): array|stdClass
    {
        return $body === [] ? new stdClass() : $body;
    }

    /**
     * Endpoint url-ini qurur.
     *
     * Dəyişən hissələr `{ad}` şəklində yazılır və `$params`-dan götürülüb
     * **rawurlencode** olunur. Sətir birləşdirməsindən (`'/orders/' . $id`) fərqli
     * olaraq bu, `$id`-nin içindəki `?`, `#` və `/` simvollarının url-i dəyişməsinin
     * qarşısını alır:
     *
     * ```php
     * $this->get($this->uri('/orders/{id}', ['id' => $id]));
     * ```
     *
     * Mütləq url-ə (`https://...`) icazə verilir — səhifələmə linkləri belə gəlir —
     * lakin yalnız `baseUrl` ilə eyni host-a. Əks halda `defaultHeaders()`-dakı
     * API açarı yad host-a göndərilə bilərdi.
     *
     * @param array<string, string|int|float> $params
     *
     * @throws InvalidRequest
     */
    protected function uri(string $path, array $params = []): string
    {
        if ($params !== []) {
            $replacements = [];

            foreach ($params as $name => $value) {
                $replacements['{' . $name . '}'] = rawurlencode((string) $value);
            }

            $path = strtr($path, $replacements);
        }

        if (preg_match('/\{[^}]*\}/', $path) === 1) {
            throw new InvalidRequest(sprintf('URI template "%s" has unfilled placeholders.', $path));
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) === 1) {
            $this->assertSameHost($path);

            return $path;
        }

        // Query və fragment `$query` parametrinin işidir; path-də görünməsi demək olar
        // həmişə kodlanmamış istifadəçi dəyəridir.
        if (str_contains($path, '?') || str_contains($path, '#')) {
            throw new InvalidRequest(sprintf(
                'Path "%s" may not contain "?" or "#". Pass query parameters as $query, '
                . 'and build dynamic segments with uri($template, $params).',
                $path,
            ));
        }

        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Mütləq url-in host-u `baseUrl` ilə eyni olmalıdır.
     *
     * Başqa host-lara (məs., ayrıca fayl serverinə) icazə vermək üçün alt class
     * bu metodu genişləndirə bilər.
     *
     * @throws InvalidRequest
     */
    protected function assertSameHost(string $uri): void
    {
        $host = parse_url($uri, PHP_URL_HOST);
        $baseHost = parse_url($this->baseUrl, PHP_URL_HOST);

        if (!is_string($host) || !is_string($baseHost) || strcasecmp($host, $baseHost) !== 0) {
            throw new InvalidRequest(sprintf(
                'Refusing to send a request to "%s": host does not match the client base url "%s". '
                . 'Override assertSameHost() if this is intentional.',
                $uri,
                $this->baseUrl,
            ));
        }
    }
}
