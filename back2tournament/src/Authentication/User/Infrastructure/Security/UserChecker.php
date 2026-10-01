<?php

declare(strict_types=1);

namespace App\Authentication\User\Infrastructure\Security;

use App\Authentication\User\Domain\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isDeleted()) {
            throw new CustomUserMessageAccountStatusException('this account was deleted');
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('please verify your email before logging in');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
