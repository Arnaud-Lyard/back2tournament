<?php

declare(strict_types=1);

namespace App\Blog\Category\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Category\Application\Model\FindCategoriesQuery;
use App\Blog\Category\Domain\Repository\CategoryRepositoryInterface;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindCategoriesHandler
{
    private CategoryRepositoryInterface $categoryRepository;
    private NormalizerInterface $serializer;
    private CurrentUserProviderInterface $currentUserProvider;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        NormalizerInterface $serializer,
        CurrentUserProviderInterface $currentUserProvider,
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
        $this->currentUserProvider = $currentUserProvider;
    }

    public function __invoke(FindCategoriesQuery $findCategoriesQuery): string
    {
        if (!$this->currentUserProvider->isGranted('ROLE_EDITOR')) {
            throw new PermissionDeniedException('the user does not have the necessary permissions');
        }

        $categories = $this->categoryRepository->findBy([], ['name' => 'ASC', 'id' => 'ASC']);

        $normalized = [];
        foreach ($categories as $category) {
            $normalized[] = $this->serializer->normalize($category);
        }

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
