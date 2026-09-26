<?php

declare(strict_types=1);

namespace App\Authentication\User\Domain\Security;

use App\Authentication\User\Domain\Entity\User;

interface PasswordHasherInterface
{
    public function hash(User $user, string $plainPassword): string;

    public function verify(User $user, string $plainPassword): bool;
}
