<?php

declare(strict_types=1);

namespace App\Authentication\User\Infrastructure\Security;

use App\Authentication\User\Domain\Entity\User;
use App\Shared\Exception\PermissionDeniedException;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CurrentUserProvider implements CurrentUserProviderInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
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
}
