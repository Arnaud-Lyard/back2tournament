<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\ChangeArticleImageCommand;
use App\Blog\Article\Application\Service\ChangeArticleImageHandler;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Event\ArticleUpdatedEvent;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Blog\Shared\Domain\Provider\AuthorProviderInterface;
use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Shared\Domain\Provider\ImageProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ArticleBodyValueObject;
use App\Shared\ValueObject\ArticleTitleValueObject;
use App\Shared\ValueObject\UploadedImageValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ChangeArticleImageHandlerTest extends TestCase
{
    private const ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const EDITOR_ID = '22222222-2222-4222-8222-222222222222';
    private const AUTHOR_ID = '33333333-3333-4333-8333-333333333333';
    private const CATEGORY_ID = '44444444-4444-4444-8444-444444444444';

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAYCAIAAAAUMWhjAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAJklEQVRIiWM8oaHBQEvARFPTRy0YtWDUglELRi0YtWDUglELqAYA1J4BSIDvLE0AAAAASUVORK5CYII=';

    /** @var list<string> */
    private array $log = [];

    public function test_an_editor_gives_an_article_its_cover_and_the_former_one_goes_once_it_is_saved(): void
    {
        $article = $this->article(image: 'articles/old.webp');

        $payload = $this->read($this->handler($article)(new ChangeArticleImageCommand(self::ARTICLE_ID, base64_decode(self::PNG, true))));

        $this->assertSame('articles/new.webp', $article->getImage());
        $this->assertSame(['store article image/png', 'save', 'dispatch '.ArticleUpdatedEvent::class, 'remove articles/old.webp'], $this->log);
        $this->assertSame('writer', $payload['authorName']);
    }

    public function test_an_editor_takes_the_cover_away(): void
    {
        $article = $this->article(image: 'articles/old.webp');

        $this->handler($article)(new ChangeArticleImageCommand(self::ARTICLE_ID, null));

        $this->assertNull($article->getImage());
        $this->assertSame(['save', 'dispatch '.ArticleUpdatedEvent::class, 'remove articles/old.webp'], $this->log);
    }

    public function test_taking_away_a_cover_the_article_does_not_have_changes_nothing(): void
    {
        $this->handler($this->article(image: null))(new ChangeArticleImageCommand(self::ARTICLE_ID, null));

        $this->assertSame([], $this->log);
    }

    public function test_only_an_editor_illustrates_an_article(): void
    {
        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('only an editor illustrates an article');

        $this->handler($this->article(image: null), editor: false, untouched: true)(new ChangeArticleImageCommand(self::ARTICLE_ID, base64_decode(self::PNG, true)));
    }

    public function test_a_file_that_is_no_image_is_refused_before_anything_is_read(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler($this->article(image: null), untouched: true)(new ChangeArticleImageCommand(self::ARTICLE_ID, 'not an image, only words'));
    }

    public function test_an_unknown_article_is_not_found_and_nothing_is_stored(): void
    {
        $this->expectException(NotFoundException::class);

        $this->handler(null, untouched: true)(new ChangeArticleImageCommand(self::ARTICLE_ID, base64_decode(self::PNG, true)));
    }

    private function handler(?Article $article, bool $editor = true, bool $untouched = false): ChangeArticleImageHandler
    {
        [$articleRepository, $imageProvider, $eventDispatcher] = $untouched ? $this->untouched() : $this->logged();
        $articleRepository->method('findOneBy')->willReturn($article);

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn(new User(self::EDITOR_ID)->setUsername('editor'));
        $currentUserProvider->method('isGranted')->willReturnCallback(static fn (string $role): bool => $editor && 'ROLE_EDITOR' === $role);

        $authorProvider = $this->createStub(AuthorProviderInterface::class);
        $authorProvider->method('usernames')->willReturn([self::AUTHOR_ID => 'writer']);

        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturn(['id' => ['value' => self::ARTICLE_ID]]);

        return new ChangeArticleImageHandler($articleRepository, $currentUserProvider, $imageProvider, $authorProvider, $eventDispatcher, $normalizer);
    }

    /** @return array{(ArticleRepositoryInterface & Stub), ImageProviderInterface, EventDispatcherInterface} */
    private function logged(): array
    {
        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('save')->willReturnCallback(function (): void {
            $this->log[] = 'save';
        });

        $imageProvider = $this->createStub(ImageProviderInterface::class);
        $imageProvider->method('store')->willReturnCallback(function (UploadedImageValueObject $image, ImageKind $kind): string {
            $this->log[] = \sprintf('store %s %s', 'articles' === $kind->value ? 'article' : $kind->value, $image->getType());

            return 'articles/new.webp';
        });
        $imageProvider->method('remove')->willReturnCallback(function (?string $key): void {
            $this->log[] = 'remove '.$key;
        });

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(function (object $event): object {
            $this->log[] = 'dispatch '.$event::class;

            return $event;
        });

        return [$articleRepository, $imageProvider, $eventDispatcher];
    }

    /** @return array{(ArticleRepositoryInterface & MockObject), ImageProviderInterface, EventDispatcherInterface} */
    private function untouched(): array
    {
        $articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $articleRepository->expects($this->never())->method('save');

        $imageProvider = $this->createMock(ImageProviderInterface::class);
        $imageProvider->expects($this->never())->method('store');
        $imageProvider->expects($this->never())->method('remove');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        return [$articleRepository, $imageProvider, $eventDispatcher];
    }

    private function article(?string $image): Article
    {
        $article = Article::create(
            new ArticleId(self::ARTICLE_ID),
            new ArticleTitleValueObject('Title'),
            new ArticleBodyValueObject('Body'),
            new CategoryId(self::CATEGORY_ID),
        );
        Article::publish($article, new AuthorId(self::AUTHOR_ID));
        if (null !== $image) {
            Article::illustrate($article, $image);
        }
        $article->pullDomainEvents();

        return $article;
    }

    /** @return array<string, mixed> */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
