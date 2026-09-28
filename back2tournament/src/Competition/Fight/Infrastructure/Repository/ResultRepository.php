<?php

declare(strict_types=1);

namespace App\Competition\Fight\Infrastructure\Repository;

use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Result> */
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

    public function findSettledAgainstOtherClans(string $clanId, int $limit, int $offset): array
    {
        return $this->settledAgainstOtherClans($clanId)
            ->orderBy('result.updatedAt', 'DESC')
            ->addOrderBy('result.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countSettledAgainstOtherClans(string $clanId): int
    {
        return (int) $this->settledAgainstOtherClans($clanId)
            ->select('COUNT(result.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function settledAgainstOtherClans(string $clanId): QueryBuilder
    {
        $settled = [ResultStatus::WIN, ResultStatus::LOSS, ResultStatus::DRAW];

        return $this->createQueryBuilder('result')
            ->andWhere('result.clan = :clan')
            ->andWhere('result.status IN (:settled)')
            ->andWhere(\sprintf(
                'EXISTS (SELECT opponent.id FROM %s opponent WHERE opponent.fight = result.fight AND opponent.id <> result.id AND opponent.clan IS NOT NULL AND opponent.clan <> :clan)',
                Result::class,
            ))
            ->setParameter('clan', $clanId)
            ->setParameter('settled', array_map(static fn (ResultStatus $status): string => $status->value, $settled));
    }
}
