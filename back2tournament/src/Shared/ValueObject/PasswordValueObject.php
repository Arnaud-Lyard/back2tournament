<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

abstract class PasswordValueObject
{
    protected $value;

    public function __construct(string $value)
    {
        $this->ensureIsValidPassword($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    protected function ensureIsValidPassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new ValidationException('Password must be at least 8 characters long');
        }
        if (!preg_match("/\d/", $password)) {
            throw new ValidationException("Password should contain at least one digit");
        }
        if (!preg_match("/[A-Z]/", $password)) {
            throw new ValidationException("Password should contain at least one Capital Letter");
        }
        if (!preg_match("/[a-z]/", $password)) {
            throw new ValidationException("Password should contain at least one small Letter");
        }
        if (!preg_match("/\W/", $password)) {
            throw new ValidationException("Password should contain at least one special character");
        }
        if (preg_match("/\s/", $password)) {
            throw new ValidationException("Password should not contain any white space");
        }

    }
}
