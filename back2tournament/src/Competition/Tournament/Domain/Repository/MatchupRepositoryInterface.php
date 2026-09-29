<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Domain\Repository;

use App\Competition\Tournament\Domain\Entity\Matchup;

interface MatchupRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Matchup> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function save(Matchup $matchup): void;
}
