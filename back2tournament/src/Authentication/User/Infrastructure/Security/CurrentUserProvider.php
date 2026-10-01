<?php

declare(strict_types=1);

namespace App\Authentication\User\Infrastructure\Security;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\PasswordHasherInterface;
use App\Shared\Exception\PermissionDeniedException;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CurrentUserProvider implements CurrentUserProviderInterface
{
    public const WRONG_PASSWORD = 'the password does not match';

    private Security $security;
    private PasswordHasherInterface $passwordHasher;

    public function __construct(Security $security, PasswordHasherInterface $passwordHasher)
    {
        $this->security = $security;
        $this->passwordHasher = $passwordHasher;
    }

    public function getUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new PermissionDeniedException('no authenticated user');
        }

        return $user;
    }

    public function isGranted(string $role): bool
    {
        return $this->security->isGranted($role);
    }

    public function confirmPassword(#[\SensitiveParameter] string $password): void
    {
        if (!$this->passwordHasher->verify($this->getUser(), $password)) {
            throw new PermissionDeniedException(self::WRONG_PASSWORD);
        }
    }
}
