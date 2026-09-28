<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Domain\Repository;

use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Enum\TournamentStatus;

interface TournamentRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Tournament> */
    public function findPage(?string $gameId, ?TournamentStatus $status, int $limit, int $offset): array;

    public function countPage(?string $gameId, ?TournamentStatus $status): int;

    public function save(Tournament $tournament): void;
}
