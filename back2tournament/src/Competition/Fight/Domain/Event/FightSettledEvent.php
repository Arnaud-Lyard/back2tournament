<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Event;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class FightSettledEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected FightId $fightId;
    protected ?CompetitorId $winner;
    protected ?TournamentId $tournamentId;

    public function __construct(FightId $fightId, ?CompetitorId $winner, ?TournamentId $tournamentId)
    {
        $this->fightId = $fightId;
        $this->winner = $winner;
        $this->tournamentId = $tournamentId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getFightId(): FightId
    {
        return $this->fightId;
    }

    public function getWinner(): ?CompetitorId
    {
        return $this->winner;
    }

    public function getTournamentId(): ?TournamentId
    {
        return $this->tournamentId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
