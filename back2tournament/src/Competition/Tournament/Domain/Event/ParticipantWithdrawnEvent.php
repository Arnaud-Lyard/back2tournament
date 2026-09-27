<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Domain\Event;

use App\Competition\Tournament\Domain\Entity\ParticipantId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ParticipantWithdrawnEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected ParticipantId $participantId;

    public function __construct(ParticipantId $participantId)
    {
        $this->participantId = $participantId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getParticipantId(): ParticipantId
    {
        return $this->participantId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
