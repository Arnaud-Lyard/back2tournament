<?php

declare(strict_types=1);

namespace App\Blog\Article\Domain\Entity;

use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\CommentId;

class Comment
{
    private string $id;

    private string $message;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    private string $articleId;

    private ?string $author = null;

    public function __construct(CommentId $commentId)
    {
        $this->id = $commentId->getValue();
    }

    public function getId(): CommentId
    {
        return new CommentId($this->id);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getArticleId(): ArticleId
    {
        return new ArticleId($this->articleId);
    }

    public function setArticleId(ArticleId $articleId): void
    {
        $this->articleId = $articleId->getValue();
    }

    public function getAuthor(): ?AuthorId
    {
        return null === $this->author ? null : new AuthorId($this->author);
    }

    public function setAuthor(AuthorId $author): void
    {
        $this->author = $author->getValue();
    }
}
