<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Infrastructure\Repository;

use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
final class PlayerRepository extends ServiceEntityRepository implements PlayerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    public function save(Player $player): void
    {
        $this->getEntityManager()->persist($player);
        $this->getEntityManager()->flush();
    }

    public function remove(Player $player): void
    {
        $this->getEntityManager()->remove($player);
        $this->getEntityManager()->flush();
    }

    public function findPage(string $gameId, ?string $search, int $limit, int $offset): array
    {
        return $this->filtered($gameId, $search)
            ->orderBy('player.createdAt', 'ASC')
            ->addOrderBy('player.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countPage(string $gameId, ?string $search): int
    {
        return (int) $this->filtered($gameId, $search)
            ->select('COUNT(player.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findNamed(string $search, ?string $gameId, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('player')
            ->andWhere('LOWER(player.battletag) LIKE :search')
            ->setParameter('search', '%'.mb_strtolower($search).'%')
            ->orderBy('player.battletag', 'ASC')
            ->setMaxResults($limit);

        if (null !== $gameId) {
            $queryBuilder->andWhere('player.game = :gameId')->setParameter('gameId', $gameId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    private function filtered(string $gameId, ?string $search): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('player')
            ->andWhere('player.game = :gameId')
            ->setParameter('gameId', $gameId);

        if (null !== $search) {
            $queryBuilder
                ->andWhere('LOWER(player.battletag) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        return $queryBuilder;
    }
}
