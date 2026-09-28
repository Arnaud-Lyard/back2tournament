<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Domain\Repository;

use App\Competition\Profile\Clan\Domain\Entity\Clan;

interface ClanRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Clan> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @return list<Clan> */
    public function findPage(string $gameId, ?string $search, int $limit, int $offset): array;

    public function countPage(string $gameId, ?string $search): int;

    public function save(Clan $clan): void;
}
