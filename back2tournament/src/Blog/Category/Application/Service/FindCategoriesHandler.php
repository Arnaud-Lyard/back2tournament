<?php

declare(strict_types=1);

namespace App\Blog\Category\Application\Service;

use App\Blog\Category\Application\Model\FindCategoriesQuery;
use App\Blog\Category\Domain\Repository\CategoryRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindCategoriesHandler
{
    private CategoryRepositoryInterface $categoryRepository;
    private NormalizerInterface $serializer;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        NormalizerInterface $serializer,
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
    }

    public function __invoke(FindCategoriesQuery $findCategoriesQuery): string
    {
        $categories = $this->categoryRepository->findBy([], ['name' => 'ASC', 'id' => 'ASC']);

        $normalized = [];
        foreach ($categories as $category) {
            $normalized[] = $this->serializer->normalize($category);
        }

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
