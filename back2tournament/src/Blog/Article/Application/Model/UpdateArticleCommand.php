<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Model;

final class UpdateArticleCommand
{
    private string $articleId;

    private ?string $title;

    private ?string $body;

    private ?string $categorySlug;

    private ?string $titleEn;

    private ?string $bodyEn;

    public function __construct(
        string $articleId,
        ?string $title,
        ?string $body,
        ?string $categorySlug,
        ?string $titleEn = null,
        ?string $bodyEn = null,
    ) {
        $this->articleId = $articleId;
        $this->title = $title;
        $this->body = $body;
        $this->categorySlug = $categorySlug;
        $this->titleEn = $titleEn;
        $this->bodyEn = $bodyEn;
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

    public function getTitleEn(): ?string
    {
        return $this->titleEn;
    }

    public function getBodyEn(): ?string
    {
        return $this->bodyEn;
    }
}
