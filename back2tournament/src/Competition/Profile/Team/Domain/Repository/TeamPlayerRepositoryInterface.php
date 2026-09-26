<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Domain\Repository;

use App\Competition\Profile\Team\Domain\Entity\TeamPlayer;

interface TeamPlayerRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function save(TeamPlayer $teamPlayer): void;
}
