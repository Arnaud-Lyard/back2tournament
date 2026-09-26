<?php

declare(strict_types=1);

namespace App\Competition\Fight\Infrastructure\Repository;

use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Result>
 */
final class ResultRepository extends ServiceEntityRepository implements ResultRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Result::class);
    }

    public function save(Result $result): void
    {
        $this->getEntityManager()->persist($result);
        $this->getEntityManager()->flush();
    }
}
