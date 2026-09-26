<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Domain\Event;

use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class CompetitorCreatedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected CompetitorId $competitorId;

    public function __construct(CompetitorId $competitorId)
    {
        $this->competitorId = $competitorId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getCompetitorId(): CompetitorId
    {
        return $this->competitorId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
