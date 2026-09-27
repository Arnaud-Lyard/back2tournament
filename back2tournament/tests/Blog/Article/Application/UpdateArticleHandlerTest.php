<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\UpdateArticleCommand;
use App\Blog\Article\Application\Service\UpdateArticleHandler;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Event\ArticleUpdatedEvent;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Blog\Shared\Domain\Provider\AuthorProviderInterface;
use App\Blog\Shared\Domain\Provider\CategoryIdProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ArticleBodyValueObject;
use App\Shared\ValueObject\ArticleTitleValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UpdateArticleHandlerTest extends TestCase
{
    private const ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const PUBLISHER_ID = '22222222-2222-4222-8222-222222222222';
    private const CATEGORY_ID = '33333333-3333-4333-8333-333333333333';
    private const OTHER_CATEGORY_ID = '44444444-4444-4444-8444-444444444444';

    public function test_an_editor_changes_the_title_the_body_and_the_category(): void
    {
        $article = $this->published();

        $articleRepository = $this->articleRepositoryMock($article);
        $articleRepository->expects($this->once())->method('save')->with($article);

        $this->handler($articleRepository)(new UpdateArticleCommand(self::ARTICLE_ID, '  New title ', 'New body', 'guides'));

        $this->assertSame('New title', $article->getTitle());
        $this->assertSame('New body', $article->getBody());
        $this->assertSame(self::OTHER_CATEGORY_ID, $article->getCategory()->getValue());
    }

    public function test_what_is_not_sent_is_kept(): void
    {
        $article = $this->published();

        $this->handler($this->articleRepository($article))(new UpdateArticleCommand(self::ARTICLE_ID, null, 'New body', null));

        $this->assertSame('Title', $article->getTitle());
        $this->assertSame('New body', $article->getBody());
        $this->assertSame(self::CATEGORY_ID, $article->getCategory()->getValue());
    }

    public function test_the_update_is_announced(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ArticleUpdatedEvent::class))
            ->willReturnArgument(0);

        $this->handler($this->articleRepository($this->published()), eventDispatcher: $eventDispatcher)(
            new UpdateArticleCommand(self::ARTICLE_ID, 'New title', null, null)
        );
    }

    public function test_the_edited_article_carries_the_name_of_its_publisher(): void
    {
        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturn(['title' => 'New title']);

        $payload = json_decode(
            $this->handler($this->articleRepository($this->published()), normalizer: $normalizer)(
                new UpdateArticleCommand(self::ARTICLE_ID, 'New title', null, null)
            ),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(['title' => 'New title', 'authorName' => 'demo'], $payload);
    }

    public function test_only_an_editor_edits_an_article(): void
    {
        $articleRepository = $this->articleRepositoryMock($this->published());
        $articleRepository->expects($this->never())->method('save');

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('only an editor edits an article');

        $this->handler($articleRepository, editor: false)(new UpdateArticleCommand(self::ARTICLE_ID, 'New title', null, null));
    }

    public function test_a_blank_title_is_refused_before_anything_is_read(): void
    {
        $articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $articleRepository->expects($this->never())->method('findOneBy');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('Article title cannot be empty');

        $this->handler($articleRepository)(new UpdateArticleCommand(self::ARTICLE_ID, '   ', null, null));
    }

    public function test_an_unknown_article_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $this->handler($this->articleRepository(null))(new UpdateArticleCommand(self::ARTICLE_ID, 'New title', null, null));
    }

    private function handler(
        ArticleRepositoryInterface $articleRepository,
        bool $editor = true,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?NormalizerInterface $normalizer = null,
    ): UpdateArticleHandler {
        $categoryIdProvider = $this->createStub(CategoryIdProviderInterface::class);
        $categoryIdProvider->method('bySlug')->willReturn(self::OTHER_CATEGORY_ID);

        $authorProvider = $this->createStub(AuthorProviderInterface::class);
        $authorProvider->method('usernames')->willReturn([self::PUBLISHER_ID => 'demo']);

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('isGranted')->willReturnCallback(static fn (string $role): bool => $editor && 'ROLE_EDITOR' === $role);

        return new UpdateArticleHandler(
            $articleRepository,
            $categoryIdProvider,
            $authorProvider,
            $currentUserProvider,
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
            $normalizer ?? $this->createStub(NormalizerInterface::class),
        );
    }

    private function articleRepository(?Article $article): ArticleRepositoryInterface
    {
        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('findOneBy')->willReturn($article);

        return $articleRepository;
    }

    /**
     * To check what is saved.
     */
    private function articleRepositoryMock(?Article $article): ArticleRepositoryInterface&MockObject
    {
        $articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $articleRepository->method('findOneBy')->willReturn($article);

        return $articleRepository;
    }

    private function published(): Article
    {
        $article = Article::create(
            new ArticleId(self::ARTICLE_ID),
            new ArticleTitleValueObject('Title'),
            new ArticleBodyValueObject('Body'),
            new CategoryId(self::CATEGORY_ID),
        );
        Article::publish($article, new AuthorId(self::PUBLISHER_ID));
        $article->pullDomainEvents();

        return $article;
    }
}
