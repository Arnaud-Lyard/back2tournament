<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Repository;

use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Enum\RankingSubject;

interface RatingRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Rating> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function count(array $criteria = []): int;

    /** @return list<Rating> */
    public function findRanking(RankingSubject $subjectType, string $gameId, int $teamSize, int $limit, int $offset): array;

    public function countRanking(RankingSubject $subjectType, string $gameId, int $teamSize): int;

    public function countAbove(RankingSubject $subjectType, string $gameId, int $teamSize, int $value): int;

    public function save(Rating $rating): void;

    public function removeAll(): void;
}
