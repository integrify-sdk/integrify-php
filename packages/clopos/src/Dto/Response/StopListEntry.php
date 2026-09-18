<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Data;

/**
 * Stop-list sətri — qalığı azalmış məhsulun limiti.
 *
 * `id` məhsulun IDsidir, `limit` isə qalan say. `timestamp` son yenilənmənin unix
 * vaxtıdır.
 */
final readonly class StopListEntry extends Data
{
    /**
     * @param int|null $id Məhsulun IDsi.
     * @param int|null $limit Qalan say.
     * @param int|null $timestamp Son yenilənmənin unix vaxtı.
     */
    public function __construct(
        public ?int $id = null,
        public ?int $limit = null,
        public ?int $timestamp = null,
    ) {
    }
}
