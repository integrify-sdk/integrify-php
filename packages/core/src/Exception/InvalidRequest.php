<?php

declare(strict_types=1);

namespace Integrify\Exception;

use InvalidArgumentException;

/**
 * Sorğu göndərilməmişdən əvvəl, klient kodundakı səhv aşkar edildikdə atılır.
 *
 * Məsələn: path-də kodlanmamış `?`/`#`, doldurulmamış `{placeholder}`, və ya
 * `baseUrl`-dən fərqli host-a yönəlmiş mütləq url.
 *
 * `RequestFailed`-dən fərqlidir: orada sorğu **göndərilib** və uğursuz olub,
 * burada isə sorğu heç yola düşməyib.
 */
final class InvalidRequest extends InvalidArgumentException implements IntegrifyException
{
}
