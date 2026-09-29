<?php

declare(strict_types=1);

namespace App\Competition\Fight\Infrastructure\Repository;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Fight> */
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

    public function findPage(?array $statuses, ?string $gameId, ?array $competitors, ?string $fightId, int $limit, int $offset): array
    {
        return $this->filtered($statuses, $gameId, $competitors, $fightId)
            ->orderBy('fight.updatedAt', 'DESC')
            ->addOrderBy('fight.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countPage(?array $statuses, ?string $gameId, ?array $competitors, ?string $fightId): int
    {
        return (int) $this->filtered($statuses, $gameId, $competitors, $fightId)
            ->select('COUNT(fight.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param (list<ResultStatus> | null) $statuses
     * @param (list<string> | null) $competitors
     */
    private function filtered(?array $statuses, ?string $gameId, ?array $competitors, ?string $fightId): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('fight');

        if (null !== $statuses) {
            $queryBuilder
                ->andWhere(\sprintf('EXISTS (SELECT result.id FROM %s result WHERE result.fight = fight.id AND result.status IN (:statuses))', Result::class))
                ->setParameter('statuses', array_map(static fn (ResultStatus $status): string => $status->value, $statuses));
        }

        if (null !== $gameId) {
            $queryBuilder->andWhere('fight.game = :gameId')->setParameter('gameId', $gameId);
        }

        if (null !== $competitors || null !== $fightId) {
            $matches = [];
            if (null !== $fightId) {
                $matches[] = 'fight.id = :fightId';
                $queryBuilder->setParameter('fightId', $fightId);
            }
            if (null !== $competitors && [] !== $competitors) {
                $matches[] = 'fight.competitorOne IN (:competitors)';
                $matches[] = 'fight.competitorTwo IN (:competitors)';
                $queryBuilder->setParameter('competitors', $competitors);
            }
            $queryBuilder->andWhere([] === $matches ? '1 = 0' : implode(' OR ', $matches));
        }

        return $queryBuilder;
    }
}
