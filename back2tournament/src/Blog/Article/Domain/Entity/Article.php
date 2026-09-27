<?php

declare(strict_types=1);

namespace App\Blog\Article\Domain\Entity;

use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Entity\Comment;
use App\Blog\Article\Domain\Entity\CommentId;
use App\Blog\Article\Domain\Enum\ArticleStatus;
use App\Blog\Article\Domain\Event\ArticleCreatedEvent;
use App\Blog\Article\Domain\Event\ArticlePublishedEvent;
use App\Blog\Article\Domain\Event\ArticleUnpublishedEvent;
use App\Blog\Article\Domain\Event\ArticleUpdatedEvent;
use App\Blog\Article\Domain\Event\CommentCreatedEvent;
use App\Shared\Aggregate\AggregateRoot;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ArticleBodyValueObject;
use App\Shared\ValueObject\ArticleTitleValueObject;

class Article extends AggregateRoot
{
    private string $id;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    private string $body;

    private string $title;

    /**
     * The user who published the article, not the one who wrote it. Null for a draft.
     */
    private ?string $author = null;

    private ArticleStatus $status = ArticleStatus::DRAFT;

    private ?\DateTimeImmutable $publishedAt = null;

    /**
     * The English version of the article: both its title and its body, or
     * neither when it has none. The site reads it in English.
     */
    private ?string $titleEn = null;

    private ?string $bodyEn = null;

    private string $category;

    public function getCategory(): CategoryId
    {
        return new CategoryId($this->category);
    }

    public function setCategory(CategoryId $category): self
    {
        $this->category = $category->getValue();

        return $this;
    }

    public function __construct(ArticleId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): ?ArticleId
    {
        return new ArticleId($this->id);
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?AuthorId
    {
        return null === $this->author ? null : new AuthorId($this->author);
    }

    public function setAuthor(?AuthorId $author): self
    {
        $this->author = $author?->getValue();

        return $this;
    }

    public function getStatus(): ArticleStatus
    {
        return $this->status;
    }

    public function setStatus(ArticleStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getTitleEn(): ?string
    {
        return $this->titleEn;
    }

    public function getBodyEn(): ?string
    {
        return $this->bodyEn;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * A new article is a draft: it has no author until someone publishes it.
     * Its English version is optional, but whole: a title and a body, or neither.
     */
    public static function create(
        ArticleId $articleId,
        ArticleTitleValueObject $title,
        ArticleBodyValueObject $body,
        CategoryId $categoryId,
        ?ArticleTitleValueObject $titleEn = null,
        ?ArticleBodyValueObject $bodyEn = null,
    ): self {
        $article = new self($articleId);
        $article->setTitle($title->getValue());
        $article->setBody($body->getValue());
        self::setEnglishVersion($article, $titleEn, $bodyEn);
        $article->setCreatedAt(new \DateTimeImmutable('now'));
        $article->setUpdatedAt(new \DateTimeImmutable('now'));
        $article->setStatus(ArticleStatus::DRAFT);
        $article->setCategory($categoryId);

        $article->recordDomainEvent(new ArticleCreatedEvent($articleId));

        return $article;
    }

    /**
     * Changes what is given; a null argument keeps the field as it is.
     */
    public static function update(
        Article $article,
        ?ArticleTitleValueObject $title,
        ?ArticleBodyValueObject $body,
        ?CategoryId $categoryId
    ): void {
        if (null !== $title) {
            $article->setTitle($title->getValue());
        }
        if (null !== $body) {
            $article->setBody($body->getValue());
        }
        if (null !== $categoryId) {
            $article->setCategory($categoryId);
        }
        $article->setUpdatedAt(new \DateTimeImmutable('now'));

        $article->recordDomainEvent(new ArticleUpdatedEvent($article->getId()));
    }

    /**
     * Gives the article its English version, or takes it away when both are
     * null. A title without a body, or a body without a title, is refused.
     */
    public static function translate(
        Article $article,
        ?ArticleTitleValueObject $titleEn,
        ?ArticleBodyValueObject $bodyEn
    ): void {
        self::setEnglishVersion($article, $titleEn, $bodyEn);
        $article->setUpdatedAt(new \DateTimeImmutable('now'));

        $article->recordDomainEvent(new ArticleUpdatedEvent($article->getId()));
    }

    /**
     * The user who publishes the article becomes its author.
     */
    public static function publish(Article $article, AuthorId $publisher): void
    {
        if (ArticleStatus::PUBLISHED === $article->getStatus()) {
            throw new ConflictException('this article is already published');
        }

        $now = new \DateTimeImmutable('now');
        $article->setStatus(ArticleStatus::PUBLISHED);
        $article->setAuthor($publisher);
        $article->setPublishedAt($now);
        $article->setUpdatedAt($now);

        $article->recordDomainEvent(new ArticlePublishedEvent($article->getId()));
    }

    /**
     * Back to draft: the article leaves the public blog, and loses its author
     * until it is published again.
     */
    public static function unpublish(Article $article): void
    {
        if (ArticleStatus::DRAFT === $article->getStatus()) {
            throw new ConflictException('this article is already a draft');
        }

        $article->setStatus(ArticleStatus::DRAFT);
        $article->setAuthor(null);
        $article->setPublishedAt(null);
        $article->setUpdatedAt(new \DateTimeImmutable('now'));

        $article->recordDomainEvent(new ArticleUnpublishedEvent($article->getId()));
    }

    private static function setEnglishVersion(
        Article $article,
        ?ArticleTitleValueObject $titleEn,
        ?ArticleBodyValueObject $bodyEn
    ): void {
        if ((null === $titleEn) !== (null === $bodyEn)) {
            throw new ValidationException('the English version needs both a title and a body');
        }

        $article->titleEn = $titleEn?->getValue();
        $article->bodyEn = $bodyEn?->getValue();
    }

    public static function createComment(
        Article $article,
        CommentId $commentId,
        string $message,
        AuthorId $author
    ): Comment {
        if (ArticleStatus::PUBLISHED !== $article->getStatus()) {
            throw new ConflictException('comments open once the article is published');
        }

        $comment = new Comment($commentId);
        $comment->setArticleId($article->getId());
        $comment->setMessage($message);
        $comment->setAuthor($author);
        $comment->setCreatedAt(new \DateTimeImmutable('now'));
        $comment->setUpdatedAt(new \DateTimeImmutable('now'));

        $article->recordDomainEvent(new CommentCreatedEvent($commentId));

        return $comment;
    }
}
