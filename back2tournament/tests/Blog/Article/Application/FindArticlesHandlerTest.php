<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Application;

use App\Blog\Article\Application\Model\FindArticlesQuery;
use App\Blog\Article\Application\Service\FindArticlesHandler;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Blog\Shared\Domain\Provider\CategoryIdProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FindArticlesHandlerTest extends TestCase
{
    private const NEWER_ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const OLDER_ARTICLE_ID = '44444444-4444-4444-8444-444444444444';
    private const AUTHOR_ID = '22222222-2222-4222-8222-222222222222';
    private const CATEGORY_ID = '33333333-3333-4333-8333-333333333333';

    public function test_the_search_and_the_offset_are_passed_to_the_repository(): void
    {
        $articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $articleRepository
            ->expects($this->once())
            ->method('findPage')
            ->with('alpha', null, 10, 20)
            ->willReturn([]);
        $articleRepository
            ->expects($this->once())
            ->method('countPage')
            ->with('alpha', null)
            ->willReturn(0);

        $handler = new FindArticlesHandler(
            $articleRepository,
            $this->categoryIdProvider(),
            $this->createStub(NormalizerInterface::class),
        );

        $handler(new FindArticlesQuery(3, 10, 'alpha'));
    }

    public function test_a_category_is_filtered_on_by_the_id_its_slug_resolves_to(): void
    {
        $articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $articleRepository
            ->expects($this->once())
            ->method('findPage')
            ->with(null, self::CATEGORY_ID, 10, 0)
            ->willReturn([]);
        $articleRepository->method('countPage')->willReturn(0);

        $handler = new FindArticlesHandler(
            $articleRepository,
            $this->categoryIdProvider(),
            $this->createStub(NormalizerInterface::class),
        );

        $handler(new FindArticlesQuery(1, 10, null, 'actualites'));
    }

    public function test_every_article_read_is_listed_in_that_order(): void
    {
        $handler = new FindArticlesHandler(
            $this->articleRepository([
                $this->article(self::NEWER_ARTICLE_ID, 'Newer'),
                $this->article(self::OLDER_ARTICLE_ID, 'Older'),
            ], 2),
            $this->categoryIdProvider(),
            $this->normalizer(),
        );

        $page = $this->read($handler(new FindArticlesQuery(1, 10)));

        $this->assertSame(
            [
                ['id' => ['value' => self::NEWER_ARTICLE_ID], 'title' => 'Newer'],
                ['id' => ['value' => self::OLDER_ARTICLE_ID], 'title' => 'Older'],
            ],
            $page['items'],
        );
    }

    public function test_the_page_carries_the_total_and_how_many_pages_it_yields(): void
    {
        $handler = new FindArticlesHandler(
            $this->articleRepository([$this->article(self::NEWER_ARTICLE_ID, 'Newer')], 42),
            $this->categoryIdProvider(),
            $this->normalizer(),
        );

        $page = $this->read($handler(new FindArticlesQuery(2, 10)));

        $this->assertSame(42, $page['total']);
        $this->assertSame(2, $page['page']);
        $this->assertSame(10, $page['limit']);
        $this->assertSame(5, $page['pages']);
    }

    public function test_no_article_reads_as_an_empty_page(): void
    {
        $handler = new FindArticlesHandler(
            $this->articleRepository([], 0),
            $this->categoryIdProvider(),
            $this->normalizer(),
        );

        $page = $this->read($handler(new FindArticlesQuery(1, 10)));

        $this->assertSame([], $page['items']);
        $this->assertSame(0, $page['total']);
        $this->assertSame(0, $page['pages']);
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, limit: int, pages: int}
     */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<Article> $articles
     */
    private function articleRepository(array $articles, int $total): ArticleRepositoryInterface
    {
        $articleRepository = $this->createStub(ArticleRepositoryInterface::class);
        $articleRepository->method('findPage')->willReturn($articles);
        $articleRepository->method('countPage')->willReturn($total);

        return $articleRepository;
    }

    private function categoryIdProvider(): CategoryIdProviderInterface
    {
        $categoryIdProvider = $this->createStub(CategoryIdProviderInterface::class);
        $categoryIdProvider->method('bySlug')->willReturn(self::CATEGORY_ID);

        return $categoryIdProvider;
    }

    private function normalizer(): NormalizerInterface
    {
        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturnCallback(
            static fn (Article $article): array => [
                'id' => ['value' => $article->getId()->getValue()],
                'title' => $article->getTitle(),
            ]
        );

        return $normalizer;
    }

    private function article(string $id, string $title): Article
    {
        return Article::create(
            new ArticleId($id),
            $title,
            'Body',
            new AuthorId(self::AUTHOR_ID),
            new CategoryId(self::CATEGORY_ID),
        );
    }
}
