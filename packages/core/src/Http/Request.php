<?php

declare(strict_types=1);

namespace Integrify\Http;

use stdClass;

/**
 * Göndəriləcək sorğunun dəyişməz (immutable) təsviri.
 *
 * PSR-7 `RequestInterface`-dən fərqli olaraq body burada hələ də massivdir —
 * JSON-a çevirmə `HttpTransport`-un işidir. Bu, testlərdə payload-u sətir kimi
 * deyil, olduğu kimi yoxlamağa imkan verir.
 */
final readonly class Request
{
    /**
     * @param string $method HTTP metodu (`GET`, `POST`, ...).
     * @param string $uri Sorğunun tam url-i.
     * @param array<array-key, mixed>|stdClass|null $body Body. PHP-də `[]` həm boş
     *     obyekt, həm boş siyahıdır; JSON-a `[]` kimi yazılır. Boş **obyekt** göndərmək
     *     üçün `new stdClass()` verin (bax: `Client::objectBody()`).
     * @param array<string, scalar> $query Query parametrləri.
     * @param array<string, string> $headers Sorğu header-ləri.
     */
    public function __construct(
        public string $method,
        public string $uri,
        public array|stdClass|null $body = null,
        public array $query = [],
        public array $headers = [],
    ) {
    }

    /**
     * Header əlavə edilmiş yeni sorğu qaytarır.
     */
    public function withHeader(string $name, string $value): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            body: $this->body,
            query: $this->query,
            headers: [...$this->headers, $name => $value],
        );
    }

    /**
     * Header-lər əlavə edilmiş yeni sorğu qaytarır.
     *
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            body: $this->body,
            query: $this->query,
            headers: [...$this->headers, ...$headers],
        );
    }

    /**
     * Body kök səviyyədə JSON array-dirsə `true`.
     *
     * Boş massiv siyahı sayılır — boş obyekt `stdClass` ilə göstərilir, ona görə
     * burada təxmin etməyə ehtiyac qalmır.
     */
    public function bodyIsList(): bool
    {
        return is_array($this->body) && array_is_list($this->body);
    }
}
