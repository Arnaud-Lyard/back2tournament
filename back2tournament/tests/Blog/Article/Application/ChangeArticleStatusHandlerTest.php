<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\ChangeArticleStatusCommand;
use App\Blog\Article\Application\Service\ChangeArticleStatusHandler;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Enum\ArticleStatus;
use App\Blog\Article\Domain\Event\ArticlePublishedEvent;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ArticleBodyValueObject;
use App\Shared\ValueObject\ArticleTitleValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ChangeArticleStatusHandlerTest extends TestCase
{
    private const ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const WRITER_ID = '22222222-2222-4222-8222-222222222222';
    private const EDITOR_ID = '33333333-3333-4333-8333-333333333333';
    private const CATEGORY_ID = '44444444-4444-4444-8444-444444444444';

    public function test_the_editor_who_publishes_a_draft_becomes_its_author(): void
    {
        $article = $this->draft();

        $articleRepository = $this->articleRepositoryMock($article);
        $articleRepository->expects($this->once())->method('save')->with($article);

        $payload = $this->read($this->handler($articleRepository)(new ChangeArticleStatusCommand(self::ARTICLE_ID, 'published')));

        $this->assertSame(ArticleStatus::PUBLISHED, $article->getStatus());
        $this->assertSame(self::EDITOR_ID, $article->getAuthor()?->getValue());
        $this->assertSame('editor', $payload['authorName']);
    }

    public function test_the_publication_is_announced(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ArticlePublishedEvent::class))
            ->willReturnArgument(0);

        $this->handler($this->articleRepository($this->draft()), eventDispatcher: $eventDispatcher)(
            new ChangeArticleStatusCommand(self::ARTICLE_ID, 'published')
        );
    }

    public function test_back_to_draft_the_article_has_no_author_any_more(): void
    {
        $article = $this->draft();
        Article::publish($article, new AuthorId(self::WRITER_ID));

        $payload = $this->read($this->handler($this->articleRepository($article))(new ChangeArticleStatusCommand(self::ARTICLE_ID, 'draft')));

        $this->assertSame(ArticleStatus::DRAFT, $article->getStatus());
        $this->assertNull($article->getAuthor());
        $this->assertNull($payload['authorName']);
    }

    public function test_only_an_editor_publishes_an_article(): void
    {
        $articleRepository = $this->articleRepositoryMock($this->draft());
        $articleRepository->expects($this->never())->method('save');

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('only an editor publishes an article');

        $this->handler($articleRepository, editor: false)(new ChangeArticleStatusCommand(self::ARTICLE_ID, 'published'));
    }

    public function test_an_unknown_status_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('status must be draft or published');

        $this->handler($this->articleRepository($this->draft()))(new ChangeArticleStatusCommand(self::ARTICLE_ID, 'archived'));
    }

    public function test_publishing_a_published_article_is_a_conflict(): void
    {
        $article = $this->draft();
        Article::publish($article, new AuthorId(self::WRITER_ID));

        $articleRepository = $this->articleRepositoryMock($article);
        $articleRepository->expects($this->never())->method('save');

        $this->expectException(ConflictException::class);

        $this->handler($articleRepository)(new ChangeArticleStatusCommand(self::ARTICLE_ID, 'published'));
    }

    public function test_an_unknown_article_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $this->handler($this->articleRepository(null))(new ChangeArticleStatusCommand(self::ARTICLE_ID, 'published'));
    }

    private function handler(
        ArticleRepositoryInterface $articleRepository,
        bool $editor = true,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): ChangeArticleStatusHandler {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn(new User(self::EDITOR_ID)->setUsername('editor'));
        $currentUserProvider->method('isGranted')->willReturnCallback(static fn (string $role): bool => $editor && 'ROLE_EDITOR' === $role);

        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturn(['id' => ['value' => self::ARTICLE_ID]]);

        return new ChangeArticleStatusHandler(
            $articleRepository,
            $currentUserProvider,
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
            $normalizer,
        );
    }

    /** @return array<string, mixed> */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function articleRepository(?Article $article): ArticleRepositoryInterface
    {
        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('findOneBy')->willReturn($article);

        return $articleRepository;
    }

    private function articleRepositoryMock(?Article $article): ArticleRepositoryInterface&MockObject
    {
        $articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $articleRepository->method('findOneBy')->willReturn($article);

        return $articleRepository;
    }

    private function draft(): Article
    {
        $article = Article::create(
            new ArticleId(self::ARTICLE_ID),
            new ArticleTitleValueObject('Title'),
            new ArticleBodyValueObject('Body'),
            new CategoryId(self::CATEGORY_ID),
        );
        $article->pullDomainEvents();

        return $article;
    }
}
