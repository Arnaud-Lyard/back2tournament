<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Model;

final class ChangeArticleImageCommand
{
    private string $articleId;
    private ?string $image;

    public function __construct(string $articleId, ?string $image)
    {
        $this->articleId = $articleId;
        $this->image = $image;
    }

    public function getArticleId(): string
    {
        return $this->articleId;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }
}
