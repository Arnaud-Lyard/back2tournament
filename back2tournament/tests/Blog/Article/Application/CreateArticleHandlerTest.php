<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Blog\Article\Application\Model\CreateArticleCommand;
use App\Blog\Article\Application\Service\CreateArticleHandler;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Enum\ArticleStatus;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class CreateArticleHandlerTest extends TestCase
{
    private const CATEGORY_ID = '33333333-3333-4333-8333-333333333333';

    public function test_a_new_article_is_saved_as_a_draft_without_author(): void
    {
        $saved = null;

        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('save')->willReturnCallback(
            static function (Article $article) use (&$saved): void {
                $saved = $article;
            }
        );

        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturn(['title' => 'Patch notes']);

        $payload = json_decode(
            $this->handler($articleRepository, $normalizer)($this->command(' Patch notes ')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertInstanceOf(Article::class, $saved);
        $this->assertSame('Patch notes', $saved->getTitle());
        $this->assertSame(self::CATEGORY_ID, $saved->getCategory()->getValue());
        $this->assertSame(ArticleStatus::DRAFT, $saved->getStatus());
        $this->assertNull($saved->getAuthor());
        $this->assertSame(['title' => 'Patch notes', 'authorName' => null], $payload);
    }

    public function test_the_english_version_is_saved_with_the_article(): void
    {
        $saved = $this->saved(fn (CreateArticleCommand $command) => $this->translated($command, ' Patch notes EN ', 'Body EN'));

        $this->assertSame(['Patch notes EN', 'Body EN'], [$saved->getTitleEn(), $saved->getBodyEn()]);
    }

    public function test_blank_english_fields_leave_the_article_in_french_only(): void
    {
        $saved = $this->saved(fn (CreateArticleCommand $command) => $this->translated($command, '  ', ''));

        $this->assertSame([null, null], [$saved->getTitleEn(), $saved->getBodyEn()]);
    }

    public function test_an_english_title_without_its_body_saves_nothing(): void
    {
        $articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $articleRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('the English version needs both a title and a body');

        $this->handler($articleRepository, $this->createStub(NormalizerInterface::class))($this->translated($this->command('Patch notes'), 'Patch notes EN', null));
    }

    public function test_a_blank_title_saves_nothing(): void
    {
        $articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $articleRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);

        $this->handler($articleRepository, $this->createStub(NormalizerInterface::class))($this->command('  '));
    }

    private function handler(ArticleRepositoryInterface $articleRepository, NormalizerInterface $normalizer): CreateArticleHandler
    {
        return new CreateArticleHandler($articleRepository, $this->createStub(EventDispatcherInterface::class), $normalizer);
    }

    /**
     * @param callable(CreateArticleCommand): CreateArticleCommand $prepare
     */
    private function saved(callable $prepare): Article
    {
        $saved = null;

        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('save')->willReturnCallback(
            static function (Article $article) use (&$saved): void {
                $saved = $article;
            }
        );

        $this->handler($articleRepository, $this->createStub(NormalizerInterface::class))($prepare($this->command('Patch notes')));

        $this->assertInstanceOf(Article::class, $saved);

        return $saved;
    }

    private function translated(CreateArticleCommand $command, ?string $titleEn, ?string $bodyEn): CreateArticleCommand
    {
        $command->setTitleEn($titleEn);
        $command->setBodyEn($bodyEn);

        return $command;
    }

    private function command(string $title): CreateArticleCommand
    {
        $command = new CreateArticleCommand();
        $command->setTitle($title);
        $command->setBody('Body');
        $command->setCategory(self::CATEGORY_ID);

        return $command;
    }
}
