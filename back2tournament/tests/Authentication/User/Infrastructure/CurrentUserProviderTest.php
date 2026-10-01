<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Infrastructure;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\PasswordHasherInterface;
use App\Authentication\User\Infrastructure\Security\CurrentUserProvider;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class CurrentUserProviderTest extends TestCase
{
    public function test_the_password_of_the_caller_is_confirmed(): void
    {
        $user = new User('11111111-1111-4111-8111-111111111111');

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->expects($this->once())->method('verify')->with($user, 'Seed1234!')->willReturn(true);

        $this->provider($user, $passwordHasher)->confirmPassword('Seed1234!');
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $passwordHasher = $this->createStub(PasswordHasherInterface::class);
        $passwordHasher->method('verify')->willReturn(false);

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessage('the password does not match');

        $this->provider(new User('11111111-1111-4111-8111-111111111111'), $passwordHasher)->confirmPassword('nope');
    }

    private function provider(User $user, PasswordHasherInterface $passwordHasher): CurrentUserProvider
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        return new CurrentUserProvider($security, $passwordHasher);
    }
}
