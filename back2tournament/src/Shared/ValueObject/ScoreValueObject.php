<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

abstract class ScoreValueObject
{
    protected $value;

    public function __construct(int $value)
    {
        $this->ensureIsValidScore($value);

        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    protected function ensureIsValidScore(int $score): void
    {
        if (0 > $score) {
            throw new ValidationException(sprintf('The score <%d> cannot be negative', $score));
        }
    }
}
