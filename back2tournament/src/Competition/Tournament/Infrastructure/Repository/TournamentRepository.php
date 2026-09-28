<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Infrastructure\Repository;

use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Enum\TournamentStatus;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Tournament> */
final class TournamentRepository extends ServiceEntityRepository implements TournamentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    public function save(Tournament $tournament): void
    {
        $this->getEntityManager()->persist($tournament);
        $this->getEntityManager()->flush();
    }

    public function findPage(?string $gameId, ?TournamentStatus $status, int $limit, int $offset): array
    {
        return $this->filtered($gameId, $status)
            ->orderBy('tournament.startsAt', 'ASC')
            ->addOrderBy('tournament.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countPage(?string $gameId, ?TournamentStatus $status): int
    {
        return (int) $this->filtered($gameId, $status)
            ->select('COUNT(tournament.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function filtered(?string $gameId, ?TournamentStatus $status): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('tournament');

        if (null !== $gameId) {
            $queryBuilder->andWhere('tournament.game = :gameId')->setParameter('gameId', $gameId);
        }

        if (null !== $status) {
            $queryBuilder->andWhere('tournament.status = :status')->setParameter('status', $status);
        }

        return $queryBuilder;
    }
}
