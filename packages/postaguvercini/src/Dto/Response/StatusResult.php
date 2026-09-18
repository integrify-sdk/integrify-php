<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\PostaGuvercini\Enum\StatusCode;

/**
 * `getStatus()` cavabı.
 */
final readonly class StatusResult extends Data
{
    /**
     * @param int|null $statusCode Sorğunun nəticə kodu. `200` uğur.
     * @param string|null $statusDescription Nəticənin izahı.
     * @param list<MessageStatus>|null $result Soruşulan mesajların vəziyyəti.
     */
    public function __construct(
        #[Field(name: 'StatusCode')]
        public ?int $statusCode = null,
        #[Field(name: 'StatusDescription')]
        public ?string $statusDescription = null,
        #[Field(name: 'Result', of: MessageStatus::class)]
        public ?array $result = null,
    ) {
    }

    /** `statusCode`-un enum qarşılığı, tanınırsa. */
    public function status(): ?StatusCode
    {
        return $this->statusCode === null ? null : StatusCode::tryFrom($this->statusCode);
    }

    /**
     * **Sorğu** uğurlu oldumu — mesajların çatdırılması ayrı sualdır.
     *
     * Hər mesajın öz vəziyyəti `statuses()` içindədir.
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode === StatusCode::Ok->value;
    }

    /**
     * Mesajların vəziyyəti — xəta halında boş siyahı.
     *
     * @return list<MessageStatus>
     */
    public function statuses(): array
    {
        return $this->result ?? [];
    }
}
