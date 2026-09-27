<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Model;

final class ChangeArticleStatusCommand
{
    private string $articleId;

    private string $status;

    /**
     * @param string $status `published` to publish the article, `draft` to take it back
     */
    public function __construct(string $articleId, string $status)
    {
        $this->articleId = $articleId;
        $this->status = $status;
    }

    public function getArticleId(): string
    {
        return $this->articleId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
