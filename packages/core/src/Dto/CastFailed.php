<?php

declare(strict_types=1);

namespace Integrify\Dto;

use RuntimeException;

/**
 * Tək bir property-nin çevirmə/validasiya xətası.
 *
 * `Mapper` bu xətaları toplayıb sonda bir `ValidationFailed`-ə çevirir, ona görə
 * kitabxanadan kənara çıxmır.
 *
 * @internal
 */
final class CastFailed extends RuntimeException
{
    /** Çevirmənin uğursuz olduğunu bildirən sentinel (`null`/`false` qanuni dəyər ola bilər). */
    public const NO_MATCH = NoMatch::Value;
}
