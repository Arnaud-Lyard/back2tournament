<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

/**
 * The title of an article, trimmed.
 */
final class ArticleTitleValueObject
{
    private const MAX_LENGTH = 255;

    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        $this->ensureIsValidArticleTitle($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function ensureIsValidArticleTitle(string $title): void
    {
        if ('' === $title) {
            throw new ValidationException('Article title cannot be empty');
        }
        if (mb_strlen($title) > self::MAX_LENGTH) {
            throw new ValidationException(sprintf('Article title must be at most %d characters long', self::MAX_LENGTH));
        }
    }
}
