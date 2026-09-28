<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Repository;

use App\Competition\Ranking\Domain\Entity\RatingChange;

interface RatingChangeRepositoryInterface
{
    public function count(array $criteria = []): int;

    public function save(RatingChange $ratingChange): void;

    public function removeAll(): void;
}
