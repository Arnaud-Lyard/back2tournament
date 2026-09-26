<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

abstract class BattletagValueObject
{
    protected $value;

    public function __construct(string $value)
    {
        $this->ensureIsValidBattletag($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    protected function ensureIsValidBattletag(string $battletag): void
    {
        if ('' === trim($battletag)) {
            throw new ValidationException('Battletag cannot be empty');
        }
        if (strlen($battletag) > 255) {
            throw new ValidationException('Battletag must be at most 255 characters long');
        }
    }
}
