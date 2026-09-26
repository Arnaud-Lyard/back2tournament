<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Domain\Repository;

use App\Competition\Profile\Team\Domain\Entity\Team;

interface TeamRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    public function save(Team $team): void;
}
