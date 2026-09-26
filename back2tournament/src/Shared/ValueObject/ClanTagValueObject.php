<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

/**
 * The short label a clan shows next to its members' names, stored upper-cased.
 */
abstract class ClanTagValueObject
{
    protected string $value;

    public function __construct(string $value)
    {
        $value = strtoupper(trim($value));

        $this->ensureIsValidClanTag($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    protected function ensureIsValidClanTag(string $tag): void
    {
        if (!preg_match('/^[A-Z0-9]{2,5}$/', $tag)) {
            throw new ValidationException(sprintf('The clan tag <%s> must be 2 to 5 letters or digits', $tag));
        }
    }
}
