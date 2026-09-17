<?php

declare(strict_types=1);

namespace Integrify\Tests\Fixtures;

use Integrify\Client;
use Integrify\Http\Transport;

/**
 * `Client` baza class-ını yoxlamaq üçün nümunə inteqrasiya klienti.
 *
 * Real inteqrasiyalar da məhz belə yazılır: adi, tipli metodlar.
 */
final class SampleClient extends Client
{
    public function __construct(Transport $transport, string $baseUrl = 'https://api.example.com/')
    {
        parent::__construct($transport, $baseUrl);
    }

    public function fetch(string $id): Sample
    {
        return $this->get('/things/' . rawurlencode($id))->to(Sample::class);
    }

    /**
     * @return list<Child>
     */
    public function search(string $term, int $page = 1): array
    {
        return $this->get('/things', ['q' => $term, 'page' => $page])->toList(Child::class);
    }

    public function create(Sample $sample): Counter
    {
        return $this->post('/things', $sample)->to(Counter::class);
    }

    /**
     * @param list<Child> $children
     */
    public function replace(array $children): Counter
    {
        return $this->put('/things', self::listBody($children))->to(Counter::class);
    }

    /**
     * @param list<Blank> $blanks
     */
    public function blanks(array $blanks): Counter
    {
        return $this->put('/blanks', self::listBody($blanks))->to(Counter::class);
    }

    public function remove(string $id): Counter
    {
        return $this->delete('/things/' . $id)->to(Counter::class);
    }

    /** Eyni host-a mütləq url — səhifələmə linkləri belə gəlir. */
    public function absolute(): Counter
    {
        return $this->get('https://api.example.com/ping')->to(Counter::class);
    }

    /** Yad host-a mütləq url — rədd edilməlidir. */
    public function offsite(): Counter
    {
        return $this->get('https://other.example.com/ping')->to(Counter::class);
    }

    /** Kodlanmamış path — `?`/`#` rədd edilməlidir. */
    public function fetchRaw(string $id): Sample
    {
        return $this->get('/things/' . $id)->to(Sample::class);
    }

    /** Doğru yol: şablon + parametr. */
    public function fetchTemplated(string $id): Sample
    {
        return $this->get($this->uri('/things/{id}', ['id' => $id]))->to(Sample::class);
    }

    public function xml(): Counter
    {
        return $this->post('/things', ['k' => 1], ['content-type' => 'application/xml'])
            ->to(Counter::class);
    }

    public function traced(): Counter
    {
        return $this->get('/things', headers: ['X-Trace' => 'abc'])->to(Counter::class);
    }

    protected function defaultHeaders(): array
    {
        return [...parent::defaultHeaders(), 'X-Client' => 'sample'];
    }
}
