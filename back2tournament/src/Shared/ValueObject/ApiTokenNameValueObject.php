<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

final class ApiTokenNameValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $value = strtolower(trim($value));
        $this->ensureIsValidApiTokenName($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function ensureIsValidApiTokenName(string $name): void
    {
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{1,39}$/', $name)) {
            throw new ValidationException(sprintf('The API token name <%s> must be 2 to 40 letters, digits, hyphens or underscores, starting with a letter or a digit', $name));
        }
    }
}
