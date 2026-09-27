<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Event;

use App\Competition\Ranking\Domain\Entity\RatingId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class RatingUpdatedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected RatingId $ratingId;

    public function __construct(RatingId $ratingId)
    {
        $this->ratingId = $ratingId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getRatingId(): RatingId
    {
        return $this->ratingId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
