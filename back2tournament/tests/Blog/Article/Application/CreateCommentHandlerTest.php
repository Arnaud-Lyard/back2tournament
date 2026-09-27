<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\CreateCommentCommand;
use App\Blog\Article\Application\Service\CreateCommentHandler;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Entity\Comment;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Article\Domain\Repository\CommentRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\ArticleBodyValueObject;
use App\Shared\ValueObject\ArticleTitleValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class CreateCommentHandlerTest extends TestCase
{
    private const ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const AUTHOR_ID = '22222222-2222-4222-8222-222222222222';
    private const CATEGORY_ID = '33333333-3333-4333-8333-333333333333';
    private const COMMENTER_ID = '44444444-4444-4444-8444-444444444444';

    public function test_the_comment_is_saved_on_the_article_in_the_name_of_the_current_user(): void
    {
        $saved = null;

        $commentRepository = $this->createStub(CommentRepositoryInterface::class);
        $commentRepository->method('save')->willReturnCallback(
            static function (Comment $comment) use (&$saved): void {
                $saved = $comment;
            }
        );

        $this->handler($this->published(), $commentRepository)($this->command());

        $this->assertInstanceOf(Comment::class, $saved);
        $this->assertSame(self::ARTICLE_ID, $saved->getArticleId()->getValue());
        $this->assertSame('Great article!', $saved->getMessage());
        $this->assertSame(self::COMMENTER_ID, $saved->getAuthor()?->getValue());
    }

    public function test_the_comment_answered_carries_the_name_of_its_author(): void
    {
        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturn(['message' => 'Great article!']);

        $payload = json_decode(
            $this->handler($this->published(), $this->createStub(CommentRepositoryInterface::class), normalizer: $normalizer)($this->command()),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(['message' => 'Great article!', 'authorName' => 'rival'], $payload);
    }

    public function test_an_unknown_article_writes_nothing(): void
    {
        $commentRepository = $this->createMock(CommentRepositoryInterface::class);
        $commentRepository->expects($this->never())->method('save');

        $this->expectException(NotFoundException::class);

        $this->handler(null, $commentRepository)($this->command());
    }

    public function test_a_draft_is_not_found_by_anyone_but_an_editor(): void
    {
        $commentRepository = $this->createMock(CommentRepositoryInterface::class);
        $commentRepository->expects($this->never())->method('save');

        $this->expectException(NotFoundException::class);

        $this->handler($this->draft(), $commentRepository)($this->command());
    }

    public function test_not_even_an_editor_comments_a_draft(): void
    {
        $commentRepository = $this->createMock(CommentRepositoryInterface::class);
        $commentRepository->expects($this->never())->method('save');

        $this->expectException(ConflictException::class);

        $this->handler($this->draft(), $commentRepository, editor: true)($this->command());
    }

    private function handler(
        ?Article $article,
        CommentRepositoryInterface $commentRepository,
        bool $editor = false,
        ?NormalizerInterface $normalizer = null,
    ): CreateCommentHandler {
        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('findOneBy')->willReturn($article);

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn(new User(self::COMMENTER_ID)->setUsername('rival'));
        $currentUserProvider->method('isGranted')->willReturnCallback(static fn (string $role): bool => $editor && 'ROLE_EDITOR' === $role);

        return new CreateCommentHandler(
            $articleRepository,
            $commentRepository,
            $currentUserProvider,
            $this->createStub(EventDispatcherInterface::class),
            $normalizer ?? $this->createStub(NormalizerInterface::class),
        );
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

    private function command(): CreateCommentCommand
    {
        $command = new CreateCommentCommand();
        $command->setArticleId(self::ARTICLE_ID);
        $command->setMessage('Great article!');

        return $command;
    }
}
