<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Repository;

use App\Competition\Fight\Domain\Entity\Result;

interface ResultRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Result> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function count(array $criteria = []): int;

    /** @return list<Result> */
    public function findSettledAgainstOtherClans(string $clanId, int $limit, int $offset): array;

    public function countSettledAgainstOtherClans(string $clanId): int;

    public function save(Result $result): void;
}
