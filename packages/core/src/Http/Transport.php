<?php

declare(strict_types=1);

namespace Integrify\Http;

use Integrify\Exception\RequestFailed;
use Integrify\Response;

/**
 * Sorğunu göndərib cavabı qaytaran qat.
 *
 * Klientlər yalnız bu interfeysdən asılıdır: real sorğular üçün `HttpTransport`,
 * testlər və payload yoxlaması üçün `RecordingTransport` istifadə olunur.
 */
interface Transport
{
    /**
     * @throws RequestFailed Şəbəkə xətası, və ya 400-dən böyük status kodu.
     */
    public function send(Request $request): Response;
}
