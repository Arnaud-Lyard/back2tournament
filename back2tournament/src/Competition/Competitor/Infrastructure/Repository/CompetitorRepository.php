<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Infrastructure\Repository;

use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Repository\CompetitorRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Competitor> */
final class CompetitorRepository extends ServiceEntityRepository implements CompetitorRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Competitor::class);
    }

    public function save(Competitor $competitor): void
    {
        $this->getEntityManager()->persist($competitor);
        $this->getEntityManager()->flush();
    }
}
