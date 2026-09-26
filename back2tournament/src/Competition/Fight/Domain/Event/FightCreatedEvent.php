<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Event;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class FightCreatedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected FightId $fightId;

    public function __construct(FightId $fightId)
    {
        $this->fightId = $fightId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getFightId(): FightId
    {
        return $this->fightId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
