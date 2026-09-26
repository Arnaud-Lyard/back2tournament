<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Domain\Event;

use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class TournamentFinishedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected TournamentId $tournamentId;

    public function __construct(TournamentId $tournamentId)
    {
        $this->tournamentId = $tournamentId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getTournamentId(): TournamentId
    {
        return $this->tournamentId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
