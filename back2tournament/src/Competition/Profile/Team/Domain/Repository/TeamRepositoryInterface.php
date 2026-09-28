<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Domain\Repository;

use App\Competition\Profile\Team\Domain\Entity\Team;

interface TeamRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Team> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @return list<Team> */
    public function findNamed(string $search, ?string $gameId, int $limit): array;

    public function save(Team $team): void;

    public function remove(Team $team): void;
}
