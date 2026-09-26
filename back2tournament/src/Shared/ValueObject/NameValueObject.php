<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

/**
 * A display name: trimmed, never blank, and bounded by the subclass.
 */
abstract class NameValueObject
{
    protected const MAX_LENGTH = 50;

    protected string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        $this->ensureIsValidName($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    protected function ensureIsValidName(string $name): void
    {
        if ('' === $name) {
            throw new ValidationException('Name cannot be empty');
        }
        if (mb_strlen($name) > static::MAX_LENGTH) {
            throw new ValidationException(sprintf('Name must be at most %d characters long', static::MAX_LENGTH));
        }
    }
}
