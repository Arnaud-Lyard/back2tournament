<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Infrastructure\Repository;

use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Game> */
final class GameRepository extends ServiceEntityRepository implements GameRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function save(Game $game): void
    {
        $this->getEntityManager()->persist($game);
        $this->getEntityManager()->flush();
    }
}
