<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Model;

final class CreateArticleCommand
{
    private string $title;

    private string $body;

    private string $category;

    private ?string $titleEn = null;

    private ?string $bodyEn = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getTitleEn(): ?string
    {
        return $this->titleEn;
    }

    public function setTitleEn(?string $titleEn): void
    {
        $this->titleEn = $titleEn;
    }

    public function getBodyEn(): ?string
    {
        return $this->bodyEn;
    }

    public function setBodyEn(?string $bodyEn): void
    {
        $this->bodyEn = $bodyEn;
    }
}
