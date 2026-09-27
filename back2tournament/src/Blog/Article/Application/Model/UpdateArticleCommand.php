<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Model;

final class UpdateArticleCommand
{
    private string $articleId;

    private ?string $title;

    private ?string $body;

    private ?string $categorySlug;

    /**
     * A null field keeps what the article has.
     */
    public function __construct(string $articleId, ?string $title, ?string $body, ?string $categorySlug)
    {
        $this->articleId = $articleId;
        $this->title = $title;
        $this->body = $body;
        $this->categorySlug = $categorySlug;
    }

    public function getArticleId(): string
    {
        return $this->articleId;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getCategorySlug(): ?string
    {
        return $this->categorySlug;
    }
}
