<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Application;

use App\Authentication\User\Application\Service\CreateUserService;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use App\Authentication\User\Domain\Security\PasswordHasherInterface;
use App\Shared\Exception\ConflictException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class CreateUserServiceTest extends TestCase
{
    public function test_creates_user_when_email_and_username_are_free(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findOneBy')->willReturn(null);
        $userRepository->expects($this->once())->method('save');

        $serializer = $this->createStub(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{}');

        $passwordHasher = $this->createStub(PasswordHasherInterface::class);
        $passwordHasher->method('hash')->willReturn('hashed-password');

        $service = new CreateUserService(
            $userRepository,
            $this->createStub(EventDispatcherInterface::class),
            $serializer,
            $passwordHasher,
        );

        $result = $service->handle('new@back2tournament.fr', 'newuser', ['ROLE_USER'], 'Password123!');

        self::assertSame('{}', $result);
    }

    public function test_rejects_when_email_already_used(): void
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria) => isset($criteria['email']) ? new User('11111111-1111-1111-1111-111111111111') : null
        );

        $service = new CreateUserService(
            $userRepository,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
            $this->createStub(PasswordHasherInterface::class),
        );

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('email already used');

        $service->handle('taken@back2tournament.fr', 'newuser', ['ROLE_USER'], 'Password123!');
    }

    public function test_rejects_when_username_already_used(): void
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria) => isset($criteria['username']) ? new User('11111111-1111-1111-1111-111111111111') : null
        );

        $service = new CreateUserService(
            $userRepository,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
            $this->createStub(PasswordHasherInterface::class),
        );

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('username already used');

        $service->handle('new@back2tournament.fr', 'taken', ['ROLE_USER'], 'Password123!');
    }
}
