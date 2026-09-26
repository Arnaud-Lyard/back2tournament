<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Infrastructure;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use App\Authentication\User\Infrastructure\Security\UserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class UserProviderTest extends TestCase
{
    public function test_loads_user_by_username(): void
    {
        $user = new User('11111111-1111-1111-1111-111111111111');

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects($this->once())
            ->method('findOneByUsernameOrEmail')
            ->with('john_doe')
            ->willReturn($user);

        $provider = new UserProvider($userRepository);

        self::assertSame($user, $provider->loadUserByIdentifier('john_doe'));
    }

    public function test_loads_user_by_email(): void
    {
        $user = new User('11111111-1111-1111-1111-111111111111');

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects($this->once())
            ->method('findOneByUsernameOrEmail')
            ->with('john@back2tournament.fr')
            ->willReturn($user);

        $provider = new UserProvider($userRepository);

        self::assertSame($user, $provider->loadUserByIdentifier('john@back2tournament.fr'));
    }

    public function test_throws_when_identifier_matches_nothing(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects($this->once())
            ->method('findOneByUsernameOrEmail')
            ->with('unknown')
            ->willReturn(null);

        $provider = new UserProvider($userRepository);

        $this->expectException(UserNotFoundException::class);

        $provider->loadUserByIdentifier('unknown');
    }
}
