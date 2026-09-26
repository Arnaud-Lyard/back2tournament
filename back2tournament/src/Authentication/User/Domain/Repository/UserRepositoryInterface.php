<?php

declare(strict_types=1);

namespace App\Authentication\User\Domain\Repository;

use App\Authentication\User\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function findOneBy(array $criteria, array|null $orderBy = null): object|null;

    public function findAll(): array;

    public function findBy(array $criteria, array|null $orderBy = null, int|null $limit = null, int|null $offset = null): array;

    public function findOneByUsernameOrEmail(string $identifier): User|null;

    public function save(User $user): void;
}
