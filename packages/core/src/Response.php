<?php

declare(strict_types=1);

namespace Integrify;

use Integrify\Dto\Data;
use Integrify\Exception\ValidationFailed;
use JsonException;

/**
 * Servisdən gələn xam cavab.
 *
 * Klient metodları adətən bunu birbaşa qaytarmır — `to()` / `toList()` ilə konkret
 * DTO-ya çevirib qaytarırlar. Cavabın özünə (status, header, xam body) yalnız
 * `RequestFailed` exception-ı vasitəsilə, və ya `Transport` səviyyəsində baxılır.
 */
final readonly class Response
{
    /**
     * @param int $status HTTP status kodu.
     * @param array<string, array<string>> $headers Cavabın header-ləri.
     * @param string $body Xam body mətni.
     */
    public function __construct(
        public int $status,
        public array $headers,
        public string $body,
    ) {
    }

    /**
     * Testlər üçün JSON cavabı qurur.
     *
     * @param array<array-key, mixed> $payload
     */
    public static function json(array $payload, int $status = 200): self
    {
        return new self(
            status: $status,
            headers: ['Content-Type' => ['application/json']],
            body: json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }

    public function isSuccessful(): bool
    {
        return $this->status < 400;
    }

    /**
     * Header-in **ilk** dəyəri.
     *
     * `Set-Cookie`, `Link` kimi təkrarlana bilən header-lər üçün `headerValues()`
     * istifadə edin — bu metod yalnız birincisini qaytarır.
     */
    public function header(string $name): ?string
    {
        return $this->headerValues($name)[0] ?? null;
    }

    /**
     * Header-in bütün dəyərləri (təkrarlana bilən header-lər üçün).
     *
     * @return list<string>
     */
    public function headerValues(string $name): array
    {
        foreach ($this->headers as $key => $values) {
            if (strcasecmp($key, $name) !== 0) {
                continue;
            }

            // PSR-7 header-ləri massiv kimi verir, lakin `Response` əl ilə də
            // qurula bilər — sətir dəyəri də qəbul edirik.
            return is_string($values) ? [$values] : array_values($values);
        }

        return [];
    }

    /**
     * Body-ni massiv kimi qaytarır.
     *
     * Cavab JSON deyilsə (məs., gateway xətası zamanı HTML səhifə və ya boş body),
     * exception atmaq əvəzinə boş massiv qaytarılır.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        if (trim($this->body) === '') {
            return [];
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Body-ni verilmiş DTO-ya çevirir.
     *
     * @template T of Data
     *
     * @param class-string<T> $dto
     *
     * @return T
     *
     * @throws ValidationFailed
     */
    public function to(string $dto): Data
    {
        return $dto::from($this->toArray());
    }

    /**
     * Kök səviyyəsində array olan body-ni DTO siyahısına çevirir.
     *
     * @template T of Data
     *
     * @param class-string<T> $dto
     *
     * @return list<T>
     *
     * @throws ValidationFailed
     */
    public function toList(string $dto): array
    {
        $body = $this->toArray();

        // Kök səviyyədə **obyekt** gəlibsə (`{"a": {...}, "b": {...}}`) açarlar
        // mənalıdır; onları səssizcə ataraq siyahı uydurmaq data itkisidir.
        if (!array_is_list($body)) {
            throw new ValidationFailed($dto, [
                '*' => 'expected a JSON array at the root, got an object',
            ]);
        }

        $items = [];

        /** @var mixed $item */
        foreach ($body as $index => $item) {
            if (!is_array($item)) {
                // Əvvəllər belə elementlər səssizcə atılırdı — data itirdi.
                throw new ValidationFailed($dto, [
                    '[' . $index . ']' => sprintf('expected an object, got %s', get_debug_type($item)),
                ]);
            }

            $items[] = $dto::from($item);
        }

        return $items;
    }
}
