<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Exception;

use Integrify\Exception\IntegrifyException;
use Integrify\Exception\RequestFailed;
use Integrify\Kapitalbank\Enum\ErrorCode;
use RuntimeException;

/**
 * Bank sorğunu rədd etdi.
 *
 * Kapital Bank xətanı HTTP status kodu ilə bildirir (EPoint-dən fərqli olaraq, orada
 * hər cavab 200-dür), və body-də struktur verir:
 *
 * ```json
 * {"errorCode": "InvalidOrderState", "errorDescription": "...", "errorDetails": {...}}
 * ```
 *
 * `Transport` belə cavab üçün `RequestFailed` atır, lakin onun mesajı yalnız status
 * kodunu göstərir. Bu class həmin body-ni oxuyub **səbəbi** görünən yerə çıxarır:
 *
 * ```php
 * try {
 *     $client->refundOrder($orderId, 10);
 * } catch (RequestRejected $rejection) {
 *     $rejection->errorCode;        // 'InvalidOrderState'
 *     $rejection->code();           // ErrorCode::InvalidOrderState
 *     $rejection->description;      // bankın izahı
 *     $rejection->getPrevious();    // orijinal RequestFailed (cavab burada)
 * }
 * ```
 *
 * Python kitabxanası əvəzinə `APIResponse.ok = false` qaytarır və xətanı
 * `body.error`-da saxlayır — yəni yoxlamağı unutmaq mümkündür. Burada unutmaq
 * mümkün deyil.
 */
final class RequestRejected extends RuntimeException implements IntegrifyException
{
    /**
     * @param string|null $errorCode Bankın xam `errorCode` dəyəri.
     * @param string|null $description Bankın `errorDescription` izahı.
     * @param array<array-key, mixed>|null $details Bankın `errorDetails` strukturu.
     *     Bankın sənədləri bu strukturun formasına zəmanət vermir, ona görə tip
     *     dar deyil — açarların sətir olduğunu güman etmək olmaz.
     * @param RequestFailed $failure Orijinal xəta — cavab və sorğu onun üzərindədir.
     */
    public function __construct(
        public readonly ?string $errorCode,
        public readonly ?string $description,
        public readonly ?array $details,
        public readonly RequestFailed $failure,
    ) {
        parent::__construct(
            self::describe($errorCode, $description, $failure),
            $failure->response->status ?? 0,
            $failure,
        );
    }

    /**
     * `RequestFailed`-dən qurur, cavabın body-sini oxumağa çalışaraq.
     *
     * Body gözlənilən formatda olmaya bilər — gateway HTML xəta səhifəsi qaytara
     * bilər, və ya cavab ümumiyyətlə olmaya bilər (şəbəkə xətası). Bu halda field-lər
     * `null` qalır, lakin exception yenə də atılır: status kodu özü artıq "uğursuz"
     * deməkdir.
     */
    public static function from(RequestFailed $failure): self
    {
        $body = $failure->response?->toArray() ?? [];

        $code = $body['errorCode'] ?? null;
        $description = $body['errorDescription'] ?? null;
        $details = $body['errorDetails'] ?? null;

        return new self(
            errorCode: is_string($code) ? $code : null,
            description: is_string($description) ? $description : null,
            details: is_array($details) ? $details : null,
            failure: $failure,
        );
    }

    /**
     * `errorCode`-un enum qarşılığı, tanınırsa.
     *
     * Xam dəyər `$errorCode`-da qalır: bank sabah yeni kod əlavə edə bilər və
     * tanınmayan kod exception-un qurulmasını sındırmamalıdır.
     */
    public function code(): ?ErrorCode
    {
        return $this->errorCode === null ? null : ErrorCode::tryFrom($this->errorCode);
    }

    private static function describe(?string $code, ?string $description, RequestFailed $failure): string
    {
        $status = $failure->response?->status;

        if ($code === null && $description === null) {
            return sprintf(
                'Kapital Bank rejected the request with HTTP %s, and the body carried no errorCode.',
                $status === null ? 'error' : (string) $status,
            );
        }

        return sprintf(
            'Kapital Bank rejected the request: %s%s',
            $code ?? 'unknown error',
            $description === null || $description === '' ? '' : ' — ' . $description,
        );
    }
}
