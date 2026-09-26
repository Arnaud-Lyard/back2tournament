<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class ArticleFinderHandlerTest extends TestCase
{
    private const ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const AUTHOR_ID = '22222222-2222-4222-8222-222222222222';
    private const CATEGORY_ID = '33333333-3333-4333-8333-333333333333';
    private const FIRST_COMMENT_ID = '44444444-4444-4444-8444-444444444444';

    public function test_the_article_carries_its_comments(): void
    {
        $article = $this->article();

        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('findOneBy')->willReturn($article);

        $commentRepository = $this->createStub(CommentRepositoryInterface::class);
        $commentRepository->method('findBy')->willReturn([$this->comment($article, self::FIRST_COMMENT_ID, 'First')]);

        $handler = new ArticleFinderHandler($articleRepository, $commentRepository, $this->serializer());

        $payload = json_decode($handler(new FindArticleQuery(self::ARTICLE_ID)), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('First', $payload['comments'][0]['message']);
        $this->assertSame(['value' => self::FIRST_COMMENT_ID], $payload['comments'][0]['id']);
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
