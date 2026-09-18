<?php

declare(strict_types=1);

namespace Integrify\Clopos\Dto\Response;

use Integrify\Dto\Attribute\Field;
use Integrify\Dto\Data;

/**
 * Clopos-un xəta cavabındakı bir element.
 *
 * Xəta body-si həmişə siyahıdır — bir sorğuda bir neçə səbəb ola bilər:
 *
 * ```json
 * {"success": false, "error": [{"message": "...", "http_code": 404}]}
 * ```
 *
 * Bütün field-lər opsionaldır: gateway bəzən yalnız `message` qaytarır.
 */
final readonly class ErrorDetail extends Data
{
    /**
     * @param string|null $message İnsan üçün izah.
     * @param string|null $type Xətanın mənbəyi, məs. `server_side`.
     * @param string|null $exception Laravel exception-ının adı, məs. `NotFoundHttpException`.
     * @param int|null $code Servisin daxili kodu.
     * @param int|null $httpCode Xətaya uyğun HTTP status kodu.
     */
    public function __construct(
        public ?string $message = null,
        public ?string $type = null,
        public ?string $exception = null,
        public ?int $code = null,
        #[Field(name: 'http_code')]
        public ?int $httpCode = null,
    ) {
    }
}
