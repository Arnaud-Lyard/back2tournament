<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Infrastructure\Repository;

use App\Competition\Profile\Team\Domain\Entity\TeamPlayer;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TeamPlayer> */
final class TeamPlayerRepository extends ServiceEntityRepository implements TeamPlayerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamPlayer::class);
    }

    public function save(TeamPlayer $teamPlayer): void
    {
        $this->getEntityManager()->persist($teamPlayer);
        $this->getEntityManager()->flush();
    }

    public function remove(TeamPlayer $teamPlayer): void
    {
        $this->getEntityManager()->remove($teamPlayer);
        $this->getEntityManager()->flush();
    }
}
