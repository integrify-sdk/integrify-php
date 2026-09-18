<?php

declare(strict_types=1);

namespace Integrify\Clopos\Exception;

use Integrify\Clopos\Dto\Response\ErrorDetail;
use Integrify\Exception\IntegrifyException;
use Integrify\Exception\RequestFailed;
use RuntimeException;

/**
 * Clopos sorğunu rədd etdi.
 *
 * Clopos xətanı HTTP status kodu ilə bildirir və body-də struktur verir:
 *
 * ```json
 * {
 *   "success": false,
 *   "error": [
 *     {
 *       "message": "No query results for model [App\\Models\\Auth\\User] 1000",
 *       "type": "server_side",
 *       "exception": "NotFoundHttpException",
 *       "http_code": 404
 *     }
 *   ]
 * }
 * ```
 *
 * `Transport` belə cavab üçün `RequestFailed` atır, lakin onun mesajı yalnız status
 * kodunu göstərir. Bu class həmin body-ni oxuyub **səbəbi** görünən yerə çıxarır:
 *
 * ```php
 * try {
 *     $client->getUser(1000);
 * } catch (RequestRejected $rejection) {
 *     $rejection->errors[0]->exception;   // 'NotFoundHttpException'
 *     $rejection->errors[0]->httpCode;    // 404
 *     $rejection->getPrevious();          // orijinal RequestFailed (cavab burada)
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
     * @param list<ErrorDetail> $errors Servisin verdiyi xəta siyahısı. Body gözlənilən
     *     formada olmasa boş qalır — status kodu özü artıq "uğursuz" deməkdir.
     * @param string|null $summary Zərfin öz `message` field-i.
     * @param RequestFailed $failure Orijinal xəta — sorğu və cavab onun üzərindədir.
     */
    public function __construct(
        public readonly array $errors,
        public readonly ?string $summary,
        public readonly RequestFailed $failure,
    ) {
        parent::__construct(
            self::describe($errors, $summary, $failure),
            $failure->response->status ?? 0,
            $failure,
        );
    }

    /**
     * `RequestFailed`-dən qurur, cavabın body-sini oxumağa çalışaraq.
     *
     * Body HTML xəta səhifəsi ola bilər, və ya cavab ümumiyyətlə olmaya bilər (şəbəkə
     * xətası). Bu halda `errors` boş qalır, lakin exception yenə də atılır.
     */
    public static function from(RequestFailed $failure): self
    {
        $body = $failure->response?->toArray() ?? [];

        $summary = $body['message'] ?? null;
        $errors = [];

        /** @var mixed $raw */
        $raw = $body['error'] ?? null;

        if (is_array($raw)) {
            /** @var mixed $item */
            foreach ($raw as $item) {
                if (is_array($item)) {
                    $errors[] = ErrorDetail::from($item);
                }
            }
        }

        return new self(
            errors: $errors,
            summary: is_string($summary) ? $summary : null,
            failure: $failure,
        );
    }

    /**
     * Bütün xəta mesajları, sıra ilə.
     *
     * @return list<string>
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->errors as $error) {
            if ($error->message !== null) {
                $messages[] = $error->message;
            }
        }

        return $messages;
    }

    /**
     * @param list<ErrorDetail> $errors
     */
    private static function describe(array $errors, ?string $summary, RequestFailed $failure): string
    {
        $status = $failure->response->status ?? 0;

        foreach ($errors as $error) {
            if ($error->message !== null) {
                return sprintf('Clopos rejected the request (HTTP %d): %s', $status, $error->message);
            }
        }

        if ($summary !== null && $summary !== '') {
            return sprintf('Clopos rejected the request (HTTP %d): %s', $status, $summary);
        }

        return sprintf('Clopos rejected the request (HTTP %d).', $status);
    }
}
