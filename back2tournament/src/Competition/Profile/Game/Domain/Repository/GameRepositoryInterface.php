<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Domain\Repository;

use App\Competition\Profile\Game\Domain\Entity\Game;

interface GameRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Game> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function save(Game $game): void;
}
