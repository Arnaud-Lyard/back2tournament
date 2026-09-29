<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Infrastructure\Repository;

use App\Competition\Ranking\Domain\Entity\RatingChange;
use App\Competition\Ranking\Domain\Repository\RatingChangeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<RatingChange> */
final class RatingChangeRepository extends ServiceEntityRepository implements RatingChangeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RatingChange::class);
    }

    public function save(RatingChange $ratingChange): void
    {
        $this->getEntityManager()->persist($ratingChange);
        $this->getEntityManager()->flush();
    }

    public function removeAll(): void
    {
        $this->getEntityManager()->createQuery(\sprintf('DELETE FROM %s ratingChange', RatingChange::class))->execute();
    }
}
