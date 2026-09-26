<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Repository;

use App\Competition\Fight\Domain\Entity\Fight;

interface FightRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * @return list<Fight>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function save(Fight $fight): void;
}
