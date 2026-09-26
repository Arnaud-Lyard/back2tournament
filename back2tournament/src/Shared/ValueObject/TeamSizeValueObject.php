<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

/**
 * How many players stand on each side of a fight: 1 for a 1v1, 5 for a 5v5.
 */
abstract class TeamSizeValueObject
{
    public const MIN = 1;

    public const MAX = 64;

    protected int $value;

    public function __construct(int $value)
    {
        $this->ensureIsValidTeamSize($value);

        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    protected function ensureIsValidTeamSize(int $size): void
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
