<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Infrastructure\Repository;

use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
final class RatingRepository extends ServiceEntityRepository implements RatingRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function save(Rating $rating): void
    {
        $this->getEntityManager()->persist($rating);
        $this->getEntityManager()->flush();
    }

    public function findRanking(RankingSubject $subjectType, string $gameId, int $limit, int $offset): array
    {
        // Equal ratings share a rank; the one with more fights behind it is listed first.
        return $this->ranking($subjectType, $gameId)
            ->orderBy('rating.value', 'DESC')
            ->addOrderBy('rating.fights', 'DESC')
            ->addOrderBy('rating.subject', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countRanking(RankingSubject $subjectType, string $gameId): int
    {
        return (int) $this->ranking($subjectType, $gameId)
            ->select('COUNT(rating.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAbove(RankingSubject $subjectType, string $gameId, int $value): int
    {
        return (int) $this->ranking($subjectType, $gameId)
            ->select('COUNT(rating.id)')
            ->andWhere('rating.value > :value')
            ->setParameter('value', $value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function removeAll(): void
    {
        $this->getEntityManager()->createQuery(\sprintf('DELETE FROM %s rating', Rating::class))->execute();
    }

    private function ranking(RankingSubject $subjectType, string $gameId): QueryBuilder
    {
        return $this->createQueryBuilder('rating')
            ->andWhere('rating.subjectType = :subjectType')
            ->andWhere('rating.game = :gameId')
            ->setParameter('subjectType', $subjectType)
            ->setParameter('gameId', $gameId);
    }
}
