<?php

declare(strict_types=1);

namespace Integrify\Clopos;

use ArrayIterator;
use Countable;
use Integrify\Dto\Data;
use IteratorAggregate;
use Traversable;

/**
 * Səhifələnmiş siyahı — elementlər və Clopos-un zərfindəki meta.
 *
 * Clopos hər cavabı zərfə bükür: `{"success": true, "data": [...], "total": 120, ...}`.
 * Bu kitabxananın qaydası zərfi çağırana ötürməməkdir, lakin `total`-ı atmaq
 * səhifələməni mümkünsüz edərdi. Ona görə `Page` **özü siyahı kimi davranır** —
 * çağıran heç nəyi açmır:
 *
 * ```php
 * foreach ($client->listProducts() as $product) {
 *     echo $product->name;
 * }
 *
 * count($page);     // bu səhifədəki element sayı
 * $page->total;     // serverdəki ümumi say
 * $page->items;     // list<Product>, lazım olsa
 * ```
 *
 * `Data` alt class-ı **deyil**: zərf sorğunun nəticəsidir, API-nin obyekti deyil.
 *
 * @template T of Data
 *
 * @implements IteratorAggregate<int, T>
 */
final readonly class Page implements Countable, IteratorAggregate
{
    /**
     * @param list<T> $items Bu səhifədəki elementlər.
     * @param int|null $total Serverdəki ümumi element sayı. Hər endpoint qaytarmır.
     * @param list<string> $sorts Sıralamaya icazə verilən field-lər.
     * @param string|null $message Servisin mesajı.
     * @param int|null $time Sorğunun serverdə çəkdiyi vaxt (millisaniyə).
     * @param string|null $timestamp Cavabın ISO 8601 vaxtı.
     * @param int|null $unix Cavabın unix vaxtı.
     */
    public function __construct(
        public array $items,
        public ?int $total = null,
        public array $sorts = [],
        public ?string $message = null,
        public ?int $time = null,
        public ?string $timestamp = null,
        public ?int $unix = null,
    ) {
    }

    /**
     * Bu səhifədəki element sayı — `total` deyil.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Səhifə boşdursa `true` — siyahının sonuna çatmağın sadə əlaməti.
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * İlk element, yoxdursa `null`.
     *
     * @return T|null
     */
    public function first(): ?Data
    {
        return $this->items[0] ?? null;
    }
}
