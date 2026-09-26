<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Blog\Article\Application\Model\CreateCommentCommand;
use App\Blog\Article\Application\Service\CreateCommentHandler;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Entity\Comment;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Article\Domain\Repository\CommentRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Shared\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class CreateCommentHandlerTest extends TestCase
{
    private const ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const AUTHOR_ID = '22222222-2222-4222-8222-222222222222';
    private const CATEGORY_ID = '33333333-3333-4333-8333-333333333333';

    public function test_the_comment_is_saved_on_the_article(): void
    {
        $saved = null;

        $commentRepository = $this->createStub(CommentRepositoryInterface::class);
        $commentRepository->method('save')->willReturnCallback(
            static function (Comment $comment) use (&$saved): void {
                $saved = $comment;
            }
        );

        $handler = $this->handler($this->articleRepository($this->article()), $commentRepository);

        $handler($this->command());

        $this->assertInstanceOf(Comment::class, $saved);
        $this->assertSame(self::ARTICLE_ID, $saved->getArticleId()->getValue());
        $this->assertSame('Great article!', $saved->getMessage());
    }

    public function test_an_unknown_article_writes_nothing(): void
    {
        $commentRepository = $this->createMock(CommentRepositoryInterface::class);
        $commentRepository->expects($this->never())->method('save');

        $handler = $this->handler($this->articleRepository(null), $commentRepository);

        $this->expectException(NotFoundException::class);

        $handler($this->command());
    }

    private function handler(
        ArticleRepositoryInterface $articleRepository,
        CommentRepositoryInterface $commentRepository,
    ): CreateCommentHandler {
        return new CreateCommentHandler(
            $articleRepository,
            $commentRepository,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
        );
    }

    private function articleRepository(?Article $article): ArticleRepositoryInterface
    {
        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('findOneBy')->willReturn($article);

        return $articleRepository;
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

    private function command(): CreateCommentCommand
    {
        $command = new CreateCommentCommand();
        $command->setArticleId(self::ARTICLE_ID);
        $command->setMessage('Great article!');

        return $command;
    }
}
