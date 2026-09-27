<?php

declare(strict_types=1);

namespace App\Blog\Article\Domain\Event;

use App\Blog\Article\Domain\Entity\ArticleId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The title, the body or the category of an article changed.
 */
final class ArticleUpdatedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected ArticleId $articleId;

    public function __construct(ArticleId $articleId)
    {
        $this->articleId = $articleId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getArticleId(): ArticleId
    {
        return $this->articleId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
