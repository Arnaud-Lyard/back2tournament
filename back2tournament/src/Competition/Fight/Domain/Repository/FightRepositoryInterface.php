<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Repository;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Enum\ResultStatus;

interface FightRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Fight> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * @param (list<ResultStatus> | null) $statuses
     * @param (list<string> | null) $competitors
     *
     * @return list<Fight>
     */
    public function findPage(?array $statuses, ?string $gameId, ?array $competitors, ?string $fightId, int $limit, int $offset): array;

    /**
     * @param (list<ResultStatus> | null) $statuses
     * @param (list<string> | null) $competitors
     */
    public function countPage(?array $statuses, ?string $gameId, ?array $competitors, ?string $fightId): int;

    public function save(Fight $fight): void;
}
