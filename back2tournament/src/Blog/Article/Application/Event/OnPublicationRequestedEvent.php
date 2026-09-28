<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnPublicationRequestedEvent extends Event
{
    private string $title;

    private string $body;

    private string $categorySlug;

    private ?string $titleEn;

    private ?string $bodyEn;

    private string $createdArticle;

    public function __construct(string $title, string $body, string $categorySlug, ?string $titleEn = null, ?string $bodyEn = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->categorySlug = $categorySlug;
        $this->titleEn = $titleEn;
        $this->bodyEn = $bodyEn;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getCategorySlug(): string
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

    public function getCreatedArticle(): string
    {
        return $this->createdArticle;
    }

    public function setCreatedArticle(string $createdArticle): void
    {
        $this->createdArticle = $createdArticle;
    }
}
