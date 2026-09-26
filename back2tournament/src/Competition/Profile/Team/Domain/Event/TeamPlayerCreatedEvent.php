<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Domain\Event;

use App\Competition\Profile\Team\Domain\Entity\TeamPlayerId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class TeamPlayerCreatedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected TeamPlayerId $teamPlayerId;

    public function __construct(TeamPlayerId $teamPlayerId)
    {
        $this->teamPlayerId = $teamPlayerId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getTeamPlayerId(): TeamPlayerId
    {
        return $this->teamPlayerId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
