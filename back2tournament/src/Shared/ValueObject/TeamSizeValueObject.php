<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

final class TeamSizeValueObject
{
    public const MIN = 1;
    public const MAX = 64;

    private int $value;

    public function __construct(int $value)
    {
        $this->ensureIsValidTeamSize($value);

        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    private function ensureIsValidTeamSize(int $size): void
    {
        if ($size < self::MIN || $size > self::MAX) {
            throw new ValidationException(sprintf(
                'The team size <%d> must be between %d and %d',
                $size,
                self::MIN,
                self::MAX,
            ));
        }
    }
}
