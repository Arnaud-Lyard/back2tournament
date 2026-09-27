<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

/**
 * The text of an article. Kept as written: only a blank body is refused.
 */
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
