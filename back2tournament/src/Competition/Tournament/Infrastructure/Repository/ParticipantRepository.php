<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Infrastructure\Repository;

use App\Competition\Tournament\Domain\Entity\Participant;
use App\Competition\Tournament\Domain\Repository\ParticipantRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Participant> */
final class ParticipantRepository extends ServiceEntityRepository implements ParticipantRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    public function save(Participant $participant): void
    {
        $this->getEntityManager()->persist($participant);
        $this->getEntityManager()->flush();
    }

    public function remove(Participant $participant): void
    {
        $this->getEntityManager()->remove($participant);
        $this->getEntityManager()->flush();
    }

    public function countByTournament(array $tournamentIds): array
    {
        if ([] === $tournamentIds) {
            return [];
        }

        /** @var list<array{tournament: string, participants: (int | string)}> $rows */
        $rows = $this->createQueryBuilder('participant')
            ->select('participant.tournament AS tournament, COUNT(participant.id) AS participants')
            ->andWhere('participant.tournament IN (:tournamentIds)')
            ->setParameter('tournamentIds', $tournamentIds)
            ->groupBy('participant.tournament')
            ->getQuery()
            ->getArrayResult();

        $counts = array_fill_keys($tournamentIds, 0);
        foreach ($rows as $row) {
            $counts[$row['tournament']] = (int) $row['participants'];
        }

        return $counts;
    }
}
