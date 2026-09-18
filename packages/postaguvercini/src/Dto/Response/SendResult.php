<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;
use Integrify\PostaGuvercini\Enum\StatusCode;

/**
 * `sendSms()` və `sendMessages()` cavabı.
 *
 * **Servis uğursuz sorğuya da HTTP 200 qaytarır**, ona görə `RequestFailed`
 * atılmaması "göndərildi" demək deyil — `isSuccessful()` yoxlamaq məcburidir.
 */
final readonly class SendResult extends Data
{
    /**
     * @param int|null $statusCode Nəticə kodu. `200` uğur. Enum üçün `status()`.
     * @param string|null $statusDescription Nəticənin izahı.
     * @param list<SentMessage>|null $result Göndərilən mesajlar. Xəta halında `null`.
     */
    public function __construct(
        #[Field(name: 'StatusCode')]
        public ?int $statusCode = null,
        #[Field(name: 'StatusDescription')]
        public ?string $statusDescription = null,
        #[Field(name: 'Result', of: SentMessage::class)]
        public ?array $result = null,
    ) {
    }

    /** `statusCode`-un enum qarşılığı, tanınırsa. */
    public function status(): ?StatusCode
    {
        return $this->statusCode === null ? null : StatusCode::tryFrom($this->statusCode);
    }

    /** Yalnız `200` uğurdur. */
    public function isSuccessful(): bool
    {
        return $this->statusCode === StatusCode::Ok->value;
    }

    /**
     * Göndərilən mesajlar — xəta halında boş siyahı.
     *
     * `$result` `null` ola bilər, ona görə bu metod `foreach` üçün həmişə massiv verir.
     *
     * @return list<SentMessage>
     */
    public function messages(): array
    {
        return $this->result ?? [];
    }

    /**
     * Bütün mesajların IDsi — status sorğusuna birbaşa ötürülə bilər.
     *
     * @return list<string>
     */
    public function messageIds(): array
    {
        $ids = [];

        foreach ($this->messages() as $message) {
            if ($message->messageId !== null) {
                $ids[] = $message->messageId;
            }
        }

        return $ids;
    }
}
