<?php

declare(strict_types=1);

namespace App\Tests\Blog\Category\Application;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Category\Application\Model\CreateCategoryCommand;
use App\Blog\Category\Application\Service\CreateCategoryService;
use App\Blog\Category\Domain\Repository\CategoryRepositoryInterface;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class CreateCategoryServiceTest extends TestCase
{
    private const ADMIN_ID = '11111111-1111-1111-1111-111111111111';
    private const PLAIN_ID = '22222222-2222-2222-2222-222222222222';
    private const EDITOR_ID = '33333333-3333-3333-3333-333333333333';

    public function test_an_administrator_creates_a_category(): void
    {
        $categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $categoryRepository->expects($this->once())->method('save');

        $service = $this->service(new User(self::ADMIN_ID)->setRoles(['ROLE_ADMIN']), $categoryRepository);

        $service(new CreateCategoryCommand('News', 'news'));
    }

    public function test_a_plain_user_cannot_create_a_category(): void
    {
        $categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $categoryRepository->expects($this->never())->method('save');

        $service = $this->service(new User(self::PLAIN_ID), $categoryRepository);

        $this->expectException(PermissionDeniedException::class);

        $service(new CreateCategoryCommand('News', 'news'));
    }

    public function test_an_editor_cannot_create_a_category_either(): void
    {
        $categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $categoryRepository->expects($this->never())->method('save');

        $service = $this->service(new User(self::EDITOR_ID)->setRoles(['ROLE_EDITOR']), $categoryRepository);

        $this->expectException(PermissionDeniedException::class);

        $service(new CreateCategoryCommand('News', 'news'));
    }

    private function service(
        User $user,
        CategoryRepositoryInterface $categoryRepository,
    ): CreateCategoryService {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn($user);
        $currentUserProvider
            ->method('isGranted')
            ->willReturnCallback(static fn (string $role): bool => \in_array($role, $user->getRoles(), true));

        return new CreateCategoryService(
            $categoryRepository,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
            $currentUserProvider,
        );
    }
}
