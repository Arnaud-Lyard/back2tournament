<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Domain\Repository;

use App\Competition\Tournament\Domain\Entity\Participant;

interface ParticipantRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Participant> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function count(array $criteria = []): int;

    /**
     * @param list<string> $tournamentIds
     *
     * @return array<string, int>
     */
    public function countByTournament(array $tournamentIds): array;

    public function save(Participant $participant): void;

    public function remove(Participant $participant): void;
}
