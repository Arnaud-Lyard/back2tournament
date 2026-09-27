<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Domain;

use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Entity\CommentId;
use App\Blog\Article\Domain\Enum\ArticleStatus;
use App\Blog\Article\Domain\Event\ArticleCreatedEvent;
use App\Blog\Article\Domain\Event\ArticlePublishedEvent;
use App\Blog\Article\Domain\Event\ArticleUnpublishedEvent;
use App\Blog\Article\Domain\Event\ArticleUpdatedEvent;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Shared\Exception\ConflictException;
use App\Shared\ValueObject\ArticleBodyValueObject;
use App\Shared\ValueObject\ArticleTitleValueObject;
use PHPUnit\Framework\TestCase;

final class ArticleTest extends TestCase
{
    private const ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const PUBLISHER_ID = '22222222-2222-4222-8222-222222222222';
    private const CATEGORY_ID = '33333333-3333-4333-8333-333333333333';
    private const OTHER_CATEGORY_ID = '44444444-4444-4444-8444-444444444444';
    private const COMMENT_ID = '55555555-5555-4555-8555-555555555555';
    private const COMMENTER_ID = '66666666-6666-4666-8666-666666666666';

    public function test_a_new_article_is_a_draft_without_author_nor_publication_date(): void
    {
        $article = $this->draft();

        $this->assertSame(ArticleStatus::DRAFT, $article->getStatus());
        $this->assertNull($article->getAuthor());
        $this->assertNull($article->getPublishedAt());
        $this->assertSame([ArticleCreatedEvent::class], $this->classes($article->pullDomainEvents()));
    }

    public function test_the_user_who_publishes_the_article_becomes_its_author(): void
    {
        $article = $this->draft();
        $article->pullDomainEvents();

        Article::publish($article, new AuthorId(self::PUBLISHER_ID));

        $this->assertSame(ArticleStatus::PUBLISHED, $article->getStatus());
        $this->assertSame(self::PUBLISHER_ID, $article->getAuthor()?->getValue());
        $this->assertNotNull($article->getPublishedAt());
        $this->assertInstanceOf(ArticlePublishedEvent::class, $article->pullDomainEvents()[0]);
    }

    public function test_a_published_article_is_not_published_again(): void
    {
        $article = $this->published();

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('this article is already published');

        Article::publish($article, new AuthorId(self::PUBLISHER_ID));
    }

    public function test_back_to_draft_the_article_loses_its_author_and_its_publication_date(): void
    {
        $article = $this->published();
        $article->pullDomainEvents();

        Article::unpublish($article);

        $this->assertSame(ArticleStatus::DRAFT, $article->getStatus());
        $this->assertNull($article->getAuthor());
        $this->assertNull($article->getPublishedAt());
        $this->assertInstanceOf(ArticleUnpublishedEvent::class, $article->pullDomainEvents()[0]);
    }

    public function test_a_draft_is_not_taken_back_to_draft(): void
    {
        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('this article is already a draft');

        Article::unpublish($this->draft());
    }

    public function test_an_update_changes_what_is_given_and_keeps_the_rest(): void
    {
        $article = $this->published();
        $article->pullDomainEvents();

        Article::update($article, new ArticleTitleValueObject('New title'), null, new CategoryId(self::OTHER_CATEGORY_ID));

        $this->assertSame('New title', $article->getTitle());
        $this->assertSame('Body', $article->getBody());
        $this->assertSame(self::OTHER_CATEGORY_ID, $article->getCategory()->getValue());
        // Editing does not change who published it.
        $this->assertSame(ArticleStatus::PUBLISHED, $article->getStatus());
        $this->assertSame(self::PUBLISHER_ID, $article->getAuthor()?->getValue());
        $this->assertInstanceOf(ArticleUpdatedEvent::class, $article->pullDomainEvents()[0]);
    }

    public function test_a_comment_records_who_wrote_it(): void
    {
        $comment = Article::createComment($this->published(), new CommentId(self::COMMENT_ID), 'Great!', new AuthorId(self::COMMENTER_ID));

        $this->assertSame(self::COMMENTER_ID, $comment->getAuthor()?->getValue());
        $this->assertSame(self::ARTICLE_ID, $comment->getArticleId()->getValue());
    }

    public function test_a_draft_takes_no_comment(): void
    {
        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('comments open once the article is published');

        Article::createComment($this->draft(), new CommentId(self::COMMENT_ID), 'Great!', new AuthorId(self::COMMENTER_ID));
    }

    private function draft(): Article
    {
        return Article::create(
            new ArticleId(self::ARTICLE_ID),
            new ArticleTitleValueObject('Title'),
            new ArticleBodyValueObject('Body'),
            new CategoryId(self::CATEGORY_ID),
        );
    }

    private function published(): Article
    {
        $article = $this->draft();
        Article::publish($article, new AuthorId(self::PUBLISHER_ID));

        return $article;
    }

    /**
     * @param list<object> $events
     *
     * @return list<string>
     */
    private function classes(array $events): array
    {
        return array_map(static fn (object $event): string => $event::class, $events);
    }
}
