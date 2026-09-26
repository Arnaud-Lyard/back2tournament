<?php

declare(strict_types=1);

namespace App\Blog\Category\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Category\Application\Model\CreateCategoryCommand;
use App\Blog\Category\Domain\Entity\Category;
use App\Blog\Category\Domain\Repository\CategoryRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class CreateCategoryService
{
    private EventDispatcherInterface $eventDispatcher;

    private CategoryRepositoryInterface $categoryRepository;

    private SerializerInterface $serializer;

    private CurrentUserProviderInterface $currentUserProvider;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        CurrentUserProviderInterface $currentUserProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
        $this->currentUserProvider = $currentUserProvider;
    }

    public function __invoke(CreateCategoryCommand $createCategoryCommand): string
    {
        if (!$this->currentUserProvider->isGranted('ROLE_ADMIN')) {
            throw new PermissionDeniedException('the user does not have the necessary permissions');
        }

        $category = Category::create(
            new CategoryId(Uuid::v4()->toString()),
            $createCategoryCommand->getName(),
            $createCategoryCommand->getSlug()
        );

        $this->categoryRepository->save($category);

        foreach ($category->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $this->serializer->serialize($category, 'json');
    }
}
