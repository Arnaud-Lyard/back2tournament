<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

/**
 * The name of a team, trimmed.
 */
final class TeamNameValueObject
{
    private const MAX_LENGTH = 50;

    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        $this->ensureIsValidTeamName($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function ensureIsValidTeamName(string $name): void
    {
        if ('' === $name) {
            throw new ValidationException('Team name cannot be empty');
        }
        if (mb_strlen($name) > self::MAX_LENGTH) {
            throw new ValidationException(sprintf('Team name must be at most %d characters long', self::MAX_LENGTH));
        }
    }
}
