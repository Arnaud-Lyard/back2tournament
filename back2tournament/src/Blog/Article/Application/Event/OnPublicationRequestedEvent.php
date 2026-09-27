<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnPublicationRequestedEvent extends Event
{
    private string $title;

    private string $body;

    private string $categorySlug;

    private string $createdArticle;

    public function __construct(string $title, string $body, string $categorySlug)
    {
        $this->title = $title;
        $this->body = $body;
        $this->categorySlug = $categorySlug;
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

    /**
     * The created article, as the handler serialized it, handed back to the controller.
     */
    public function getCreatedArticle(): string
    {
        return $this->createdArticle;
    }

    public function setCreatedArticle(string $createdArticle): void
    {
        $this->createdArticle = $createdArticle;
    }
}
