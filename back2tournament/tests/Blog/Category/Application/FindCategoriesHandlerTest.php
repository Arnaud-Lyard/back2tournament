<?php

declare(strict_types=1);

namespace App\Tests\Blog\Category\Application;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Category\Application\Model\FindCategoriesQuery;
use App\Blog\Category\Application\Service\FindCategoriesHandler;
use App\Blog\Category\Domain\Entity\Category;
use App\Blog\Category\Domain\Repository\CategoryRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FindCategoriesHandlerTest extends TestCase
{
    private const NEWS_ID = '11111111-1111-4111-8111-111111111111';
    private const RULES_ID = '22222222-2222-4222-8222-222222222222';

    public function test_an_editor_gets_every_category_by_name(): void
    {
        $categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findBy')
            ->with([], ['name' => 'ASC', 'id' => 'ASC'])
            ->willReturn([
                new Category(new CategoryId(self::NEWS_ID), 'News', 'news'),
                new Category(new CategoryId(self::RULES_ID), 'Rules', 'rules'),
            ]);

        $handler = new FindCategoriesHandler($categoryRepository, $this->normalizer(), $this->currentUserProvider('ROLE_EDITOR'));

        $listed = json_decode($handler(new FindCategoriesQuery()), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                ['id' => self::NEWS_ID, 'name' => 'News', 'slug' => 'news'],
                ['id' => self::RULES_ID, 'name' => 'Rules', 'slug' => 'rules'],
            ],
            $listed,
        );
    }

    public function test_a_caller_below_editor_is_refused_before_any_read(): void
    {
        $categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $categoryRepository->expects($this->never())->method('findBy');

        $handler = new FindCategoriesHandler($categoryRepository, $this->normalizer(), $this->currentUserProvider('ROLE_USER'));

        $this->expectException(PermissionDeniedException::class);

        $handler(new FindCategoriesQuery());
    }

    public function test_a_tool_signed_in_with_an_api_token_gets_the_categories_to_file_its_drafts_under(): void
    {
        $categoryRepository = $this->createStub(CategoryRepositoryInterface::class);
        $categoryRepository->method('findBy')->willReturn([new Category(new CategoryId(self::NEWS_ID), 'News', 'news')]);

        $handler = new FindCategoriesHandler($categoryRepository, $this->normalizer(), $this->currentUserProvider('ROLE_BOT'));

        $listed = json_decode($handler(new FindCategoriesQuery()), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([['id' => self::NEWS_ID, 'name' => 'News', 'slug' => 'news']], $listed);
    }

    private function currentUserProvider(string $role): CurrentUserProviderInterface
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('isGranted')->willReturnCallback(
            static fn (string $granted): bool => $granted === $role
        );

        return $currentUserProvider;
    }

    private function normalizer(): NormalizerInterface
    {
        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturnCallback(
            static fn (Category $category): array => [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
            ]
        );

        return $normalizer;
    }
}
