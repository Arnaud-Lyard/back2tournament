<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

abstract class UsernameValueObject
{
    protected $value;

    public function __construct(string $value)
    {
        $this->ensureIsValidUsername($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    protected function ensureIsValidUsername(string $username): void
    {
        if (strlen($username) < 3) {
            throw new ValidationException('Username must be at least 3 characters long');
        }
        if (strlen($username) > 30) {
            throw new ValidationException('Username must be at most 30 characters long');
        }
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $username)) {
            throw new ValidationException('Username may only contain letters, numbers, underscores and hyphens');
        }
    }
}
