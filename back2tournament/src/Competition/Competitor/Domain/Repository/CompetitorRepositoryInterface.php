<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Domain\Repository;

use App\Competition\Competitor\Domain\Entity\Competitor;

interface CompetitorRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function save(Competitor $competitor): void;
}
