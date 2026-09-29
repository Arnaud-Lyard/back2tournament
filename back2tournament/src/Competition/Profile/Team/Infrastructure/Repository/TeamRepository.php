<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Infrastructure\Repository;

use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Team> */
final class TeamRepository extends ServiceEntityRepository implements TeamRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function save(Team $team): void
    {
        $this->getEntityManager()->persist($team);
        $this->getEntityManager()->flush();
    }

    public function findNamed(string $search, ?string $gameId, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('team')
            ->andWhere('LOWER(team.name) LIKE :search')
            ->setParameter('search', '%'.mb_strtolower($search).'%')
            ->orderBy('team.name', 'ASC')
            ->setMaxResults($limit);

        if (null !== $gameId) {
            $queryBuilder->andWhere('team.game = :gameId')->setParameter('gameId', $gameId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function remove(Team $team): void
    {
        $this->getEntityManager()->remove($team);
        $this->getEntityManager()->flush();
    }
}
