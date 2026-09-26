<?php

declare(strict_types=1);

namespace App\Competition\Fight\Infrastructure\Repository;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fight>
 */
final class FightRepository extends ServiceEntityRepository implements FightRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fight::class);
    }

    public function save(Fight $fight): void
    {
        $this->getEntityManager()->persist($fight);
        $this->getEntityManager()->flush();
    }
}
