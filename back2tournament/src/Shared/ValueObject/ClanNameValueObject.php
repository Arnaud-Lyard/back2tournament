<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

/**
 * The name of a clan, trimmed.
 */
final class ClanNameValueObject
{
    private const MAX_LENGTH = 50;

    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        $this->ensureIsValidClanName($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function ensureIsValidClanName(string $name): void
    {
        if ('' === $name) {
            throw new ValidationException('Clan name cannot be empty');
        }
        if (mb_strlen($name) > self::MAX_LENGTH) {
            throw new ValidationException(sprintf('Clan name must be at most %d characters long', self::MAX_LENGTH));
        }
    }
}
