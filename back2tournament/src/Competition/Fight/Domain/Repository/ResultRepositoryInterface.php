<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Repository;

use App\Competition\Fight\Domain\Entity\Result;

interface ResultRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * @return list<Result>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function count(array $criteria = []): int;

    /**
     * One page of the settled results a clan made against other clans, the
     * latest first: the results recorded as played for the clan, by one of
     * its teams or in 1v1 by one of its members, whose opponent played for
     * another clan.
     *
     * @return list<Result>
     */
    public function findSettledAgainstOtherClans(string $clanId, int $limit, int $offset): array;

    public function countSettledAgainstOtherClans(string $clanId): int;

    public function save(Result $result): void;
}
