<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

final class DeclaredStatusValueObject
{
    protected string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValidDeclaredStatus($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    protected function ensureIsValidDeclaredStatus(string $status): void
    {
        if (!\in_array($status, ['win', 'loss', 'draw'], true)) {
            throw new ValidationException(sprintf(
                'The status <%s> is not a declarable outcome',
                $status,
            ));
        }
    }
}
