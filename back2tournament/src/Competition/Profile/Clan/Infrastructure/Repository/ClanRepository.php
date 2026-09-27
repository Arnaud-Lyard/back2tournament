<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Infrastructure\Repository;

use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Clan>
 */
final class ClanRepository extends ServiceEntityRepository implements ClanRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Clan::class);
    }

    public function save(Clan $clan): void
    {
        $this->getEntityManager()->persist($clan);
        $this->getEntityManager()->flush();
    }

    public function findPage(string $gameId, ?string $search, int $limit, int $offset): array
    {
        return $this->filtered($gameId, $search)
            ->orderBy('clan.name', 'ASC')
            ->addOrderBy('clan.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countPage(string $gameId, ?string $search): int
    {
        return (int) $this->filtered($gameId, $search)
            ->select('COUNT(clan.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function filtered(string $gameId, ?string $search): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('clan')
            ->andWhere('clan.game = :gameId')
            ->setParameter('gameId', $gameId);

        if (null !== $search) {
            $queryBuilder
                ->andWhere('LOWER(clan.name) LIKE :search OR LOWER(clan.tag) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        return $queryBuilder;
    }
}
