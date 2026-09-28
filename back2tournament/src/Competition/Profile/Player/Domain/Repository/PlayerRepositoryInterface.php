<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Domain\Repository;

use App\Competition\Profile\Player\Domain\Entity\Player;

interface PlayerRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Player> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @return list<Player> */
    public function findPage(string $gameId, ?string $search, int $limit, int $offset): array;

    public function countPage(string $gameId, ?string $search): int;

    /** @return list<Player> */
    public function findNamed(string $search, ?string $gameId, int $limit): array;

    public function save(Player $player): void;

    public function remove(Player $player): void;
}
