<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Infrastructure\Repository;

use App\Competition\Tournament\Domain\Entity\Matchup;
use App\Competition\Tournament\Domain\Repository\MatchupRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Matchup>
 */
final class MatchupRepository extends ServiceEntityRepository implements MatchupRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Matchup::class);
    }

    public function save(Matchup $matchup): void
    {
        $this->getEntityManager()->persist($matchup);
        $this->getEntityManager()->flush();
    }
}
