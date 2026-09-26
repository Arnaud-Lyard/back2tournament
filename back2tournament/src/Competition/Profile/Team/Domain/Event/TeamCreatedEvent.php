<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Domain\Event;

use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class TeamCreatedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected TeamId $teamId;

    public function __construct(TeamId $teamId)
    {
        $this->teamId = $teamId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
