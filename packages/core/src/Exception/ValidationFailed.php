<?php

declare(strict_types=1);

namespace Integrify\Exception;

use InvalidArgumentException;

/**
 * DTO validasiyası uğursuz olduqda atılır.
 *
 * Bütün xətalar bir yerə toplanır — ilk xətada dayanılmır.
 */
final class ValidationFailed extends InvalidArgumentException implements IntegrifyException
{
    /**
     * @param class-string $dto Validasiya olunan DTO-nun adı.
     * @param array<string, string> $errors `field => mesaj` şəklində xətalar.
     */
    public function __construct(
        public readonly string $dto,
        public readonly array $errors,
    ) {
        parent::__construct(self::describe($dto, $errors));
    }

    /**
     * @param class-string $dto
     * @param array<string, string> $errors
     */
    private static function describe(string $dto, array $errors): string
    {
        $lines = [];

        foreach ($errors as $field => $message) {
            $lines[] = sprintf('  %s: %s', $field, $message);
        }

        return sprintf(
            "%d validation error%s for %s\n%s",
            count($errors),
            count($errors) === 1 ? '' : 's',
            $dto,
            implode("\n", $lines),
        );
    }
}
