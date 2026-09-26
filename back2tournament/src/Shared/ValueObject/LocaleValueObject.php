<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

abstract class LocaleValueObject
{
    protected $value;

    public function __construct(string $value)
    {
        $this->ensureIsValidLocale($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    protected function ensureIsValidLocale(string $locale): void
    {
        if (!\in_array($locale, ['fr', 'en'], true)) {
            throw new ValidationException(sprintf('The locale <%s> is not supported', $locale));
        }
    }
}
