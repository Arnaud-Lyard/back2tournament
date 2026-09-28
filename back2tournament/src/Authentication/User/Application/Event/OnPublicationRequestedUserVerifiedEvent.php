<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnPublicationRequestedUserVerifiedEvent extends Event
{
    private string $title;
    private string $body;
    private string $author;
    private string $categorySlug;
    private ?string $titleEn;
    private ?string $bodyEn;

    private string $createdArticle;

    public function __construct(string $title, string $body, string $author, string $categorySlug, ?string $titleEn = null, ?string $bodyEn = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->author = $author;
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

    public function getAuthor(): string
    {
        return $this->author;
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
