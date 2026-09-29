<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

final class ArticleBodyValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValidArticleBody($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function ensureIsValidArticleBody(string $body): void
    {
        if ('' === trim($body)) {
            throw new ValidationException('Article body cannot be empty');
        }
    }
}
