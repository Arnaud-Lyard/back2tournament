<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

final class ApiTokenLifetimeValueObject
{
    public const MAX_DAYS = 3650;

    private int $days;

    public function __construct(int $days)
    {
        $this->ensureIsValidApiTokenLifetime($days);

        $this->days = $days;
    }

    public function getValue(): int
    {
        return $this->days;
    }

    private function ensureIsValidApiTokenLifetime(int $days): void
    {
        if ($days < 1 || $days > self::MAX_DAYS) {
            throw new ValidationException(sprintf('An API token lasts from 1 to %d days, not <%d>', self::MAX_DAYS, $days));
        }
    }
}
