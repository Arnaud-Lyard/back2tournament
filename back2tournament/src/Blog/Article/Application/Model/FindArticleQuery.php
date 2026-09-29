<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Model;

final class FindArticleQuery
{
    private string $articleId;

    private bool $includesDrafts;

    public function __construct(string $articleId, bool $includesDrafts = false)
    {
        $this->articleId = $articleId;
        $this->includesDrafts = $includesDrafts;
    }

    public function getArticleId(): string
    {
        return $this->articleId;
    }

    public function includesDrafts(): bool
    {
        return $this->includesDrafts;
    }
}
