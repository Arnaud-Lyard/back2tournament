<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\FindArticleQuery;
use App\Blog\Article\Application\Service\ArticleFinderHandler;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Entity\Comment;
use App\Blog\Article\Domain\Entity\CommentId;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Article\Domain\Repository\CommentRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Blog\Shared\Domain\Provider\AuthorProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\ArticleBodyValueObject;
use App\Shared\ValueObject\ArticleTitleValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class ArticleFinderHandlerTest extends TestCase
{
    private const ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const AUTHOR_ID = '22222222-2222-4222-8222-222222222222';
    private const CATEGORY_ID = '33333333-3333-4333-8333-333333333333';
    private const FIRST_COMMENT_ID = '44444444-4444-4444-8444-444444444444';
    private const COMMENTER_ID = '55555555-5555-4555-8555-555555555555';

    public function test_the_article_carries_its_comments_with_the_names_of_their_authors(): void
    {
        $article = $this->published();

        $payload = $this->read($this->handler($article, [$this->comment($article, self::FIRST_COMMENT_ID, 'First')]));

        $this->assertSame('First', $payload['comments'][0]['message']);
        $this->assertSame(['value' => self::FIRST_COMMENT_ID], $payload['comments'][0]['id']);
        $this->assertSame('rival', $payload['comments'][0]['authorName']);
    }

    public function test_the_user_who_published_the_article_is_named_as_its_author(): void
    {
        $payload = $this->read($this->handler($this->published()));

        $this->assertSame('published', $payload['status']);
        $this->assertSame(['value' => self::AUTHOR_ID], $payload['author']);
        $this->assertSame('demo', $payload['authorName']);
    }

    public function test_an_editor_reads_a_draft_which_has_no_author_yet(): void
    {
        $payload = $this->read($this->handler($this->draft(), editor: true));

        $this->assertSame('draft', $payload['status']);
        $this->assertNull($payload['author']);
        $this->assertNull($payload['authorName']);
        $this->assertSame([], $payload['comments']);
    }

    public function test_a_draft_is_not_found_by_anyone_but_an_editor(): void
    {
        $this->expectException(NotFoundException::class);

        $this->handler($this->draft())(new FindArticleQuery(self::ARTICLE_ID));
    }

    public function test_an_unknown_article_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $this->handler(null, editor: true)(new FindArticleQuery(self::ARTICLE_ID));
    }

    /** @param list<Comment> $comments */
    private function handler(?Article $article, array $comments = [], bool $editor = false): ArticleFinderHandler
    {
        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('findOneBy')->willReturn($article);

        $commentRepository = $this->createStub(CommentRepositoryInterface::class);
        $commentRepository->method('findBy')->willReturn($comments);

        $authorProvider = $this->createStub(AuthorProviderInterface::class);
        $authorProvider->method('usernames')->willReturn([self::AUTHOR_ID => 'demo', self::COMMENTER_ID => 'rival']);

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('isGranted')->willReturnCallback(static fn (string $role): bool => $editor && 'ROLE_EDITOR' === $role);

        return new ArticleFinderHandler(
            $articleRepository,
            $commentRepository,
            $authorProvider,
            $currentUserProvider,
            new Serializer([new BackedEnumNormalizer(), new DateTimeNormalizer(), new ObjectNormalizer()]),
        );
    }

    /** @return array<string, mixed> */
    private function read(ArticleFinderHandler $handler): array
    {
        return json_decode($handler(new FindArticleQuery(self::ARTICLE_ID)), true, 512, JSON_THROW_ON_ERROR);
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
        Article::publish($article, new AuthorId(self::AUTHOR_ID));

        return $article;
    }

    private function comment(Article $article, string $id, string $message): Comment
    {
        return Article::createComment($article, new CommentId($id), $message, new AuthorId(self::COMMENTER_ID));
    }
}
