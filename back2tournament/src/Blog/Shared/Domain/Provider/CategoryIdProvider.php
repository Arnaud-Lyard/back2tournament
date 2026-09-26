<?php

declare(strict_types=1);

namespace App\Blog\Shared\Domain\Provider;

use App\Blog\Category\Domain\Entity\Category;
use App\Blog\Category\Domain\Repository\CategoryRepositoryInterface;
use App\Shared\Exception\NotFoundException;

final class CategoryIdProvider implements CategoryIdProviderInterface
{
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function bySlug(string $slug): string
    {
        /** @var Category|null $category */
        $category = $this->categoryRepository->findOneBy(['slug' => $slug]);
        if (!$category) {
            throw new NotFoundException(\sprintf('category with slug %s not found', $slug));
        }

        return $category->getId();
    }
}
