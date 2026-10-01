<?php

declare(strict_types=1);

namespace App\Authentication\User\Domain\Security;

use App\Authentication\User\Domain\Entity\User;

interface CurrentUserProviderInterface
{
    public function getUser(): User;

    public function isGranted(string $role): bool;

    public function confirmPassword(string $password): void;
}
