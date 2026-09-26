<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

abstract class EmailValueObject
{
    protected $value;

    public function __construct(string $value)
    {
        $this->ensureIsValidEmail($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    protected function ensureIsValidEmail(string $email): void
    {
        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(sprintf('The email <%s> is not valid', $email));
        }
    }
}
