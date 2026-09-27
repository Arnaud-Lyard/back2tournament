<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Repository;

use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Enum\RankingSubject;

interface RatingRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * @return list<Rating>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function count(array $criteria = []): int;

    /**
     * One page of the ranking of a game in one format, the highest rating first.
     *
     * @return list<Rating>
     */
    public function findRanking(RankingSubject $subjectType, string $gameId, int $teamSize, int $limit, int $offset): array;

    public function countRanking(RankingSubject $subjectType, string $gameId, int $teamSize): int;

    /**
     * How many of the ranking of a game in one format rate strictly higher
     * than $value: the rank of $value is one more.
     */
    public function countAbove(RankingSubject $subjectType, string $gameId, int $teamSize, int $value): int;

    public function save(Rating $rating): void;

    /**
     * Empties every ranking, before they are counted again from the start.
     */
    public function removeAll(): void;
}
