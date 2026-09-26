<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Domain\Event;

use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class PlayerDeletedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected PlayerId $playerId;

    public function __construct(PlayerId $playerId)
    {
        $this->playerId = $playerId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getPlayerId(): PlayerId
    {
        return $this->playerId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
