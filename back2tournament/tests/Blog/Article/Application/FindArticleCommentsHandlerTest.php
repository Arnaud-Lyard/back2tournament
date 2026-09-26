<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Blog\Article\Application\Model\FindArticleCommentsQuery;
use App\Blog\Article\Application\Service\FindArticleCommentsHandler;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Entity\Comment;
use App\Blog\Article\Domain\Entity\CommentId;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Article\Domain\Repository\CommentRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Shared\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class FindArticleCommentsHandlerTest extends TestCase
{
    private const ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const AUTHOR_ID = '22222222-2222-4222-8222-222222222222';
    private const CATEGORY_ID = '33333333-3333-4333-8333-333333333333';
    private const FIRST_COMMENT_ID = '44444444-4444-4444-8444-444444444444';
    private const SECOND_COMMENT_ID = '55555555-5555-4555-8555-555555555555';

    public function test_comments_of_the_article_are_asked_for_oldest_first(): void
    {
        $commentRepository = $this->createMock(CommentRepositoryInterface::class);
        $commentRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['articleId' => self::ARTICLE_ID], ['createdAt' => 'ASC', 'id' => 'ASC'])
            ->willReturn([]);

        $handler = new FindArticleCommentsHandler(
            $this->articleRepository($this->article()),
            $commentRepository,
            $this->createStub(NormalizerInterface::class),
        );

        $this->assertSame('[]', $handler(new FindArticleCommentsQuery(self::ARTICLE_ID)));
    }

    public function test_every_comment_of_the_article_is_listed(): void
    {
        $article = $this->article();

        $handler = new FindArticleCommentsHandler(
            $this->articleRepository($article),
            $this->commentRepository([
                $this->comment($article, self::FIRST_COMMENT_ID, 'First'),
                $this->comment($article, self::SECOND_COMMENT_ID, 'Second'),
            ]),
            $this->serializer(),
        );

        $listed = json_decode($handler(new FindArticleCommentsQuery(self::ARTICLE_ID)), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['First', 'Second'], array_column($listed, 'message'));
        $this->assertSame(['value' => self::FIRST_COMMENT_ID], $listed[0]['id']);
        $this->assertSame(['value' => self::ARTICLE_ID], $listed[0]['articleId']);
    }

    public function test_an_unknown_article_is_not_found_and_no_comment_is_read(): void
    {
        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('findOneBy')->willReturn(null);

        $commentRepository = $this->createMock(CommentRepositoryInterface::class);
        $commentRepository->expects($this->never())->method('findBy');

        $handler = new FindArticleCommentsHandler($articleRepository, $commentRepository, $this->serializer());

        $this->expectException(NotFoundException::class);

        $handler(new FindArticleCommentsQuery(self::ARTICLE_ID));
    }

    private function articleRepository(Article $article): ArticleRepositoryInterface
    {
        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('findOneBy')->willReturn($article);

        return $articleRepository;
    }

    /**
     * @param list<Comment> $comments
     */
    private function commentRepository(array $comments): CommentRepositoryInterface
    {
        $commentRepository = $this->createStub(CommentRepositoryInterface::class);
        $commentRepository->method('findBy')->willReturn($comments);

        return $commentRepository;
    }

    private function serializer(): Serializer
    {
        return new Serializer([new DateTimeNormalizer(), new ObjectNormalizer()]);
    }

    private function article(): Article
    {
        return Article::create(
            new ArticleId(self::ARTICLE_ID),
            'Title',
            'Body',
            new AuthorId(self::AUTHOR_ID),
            new CategoryId(self::CATEGORY_ID),
        );
    }

    private function comment(Article $article, string $id, string $message): Comment
    {
        return Article::createComment($article, new CommentId($id), $message);
    }
}
