<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

/**
 * The name of a tournament, trimmed.
 */
final class TournamentNameValueObject
{
    private const MAX_LENGTH = 100;

    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        $this->ensureIsValidTournamentName($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function ensureIsValidTournamentName(string $name): void
    {
        if ('' === $name) {
            throw new ValidationException('Tournament name cannot be empty');
        }
        if (mb_strlen($name) > self::MAX_LENGTH) {
            throw new ValidationException(sprintf('Tournament name must be at most %d characters long', self::MAX_LENGTH));
        }
    }
}
