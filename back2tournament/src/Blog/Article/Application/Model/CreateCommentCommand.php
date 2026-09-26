<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Model;

final class CreateCommentCommand
{
    private string $articleId;

    private string $message;

    public function getArticleId(): string
    {
        return $this->articleId;
    }

    public function setArticleId(string $articleId): void
    {
        $this->articleId = $articleId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
