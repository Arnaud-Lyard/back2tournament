<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Event;

use App\Competition\Fight\Domain\Entity\ResultId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ResultConfirmedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected ResultId $resultId;

    public function __construct(ResultId $resultId)
    {
        $this->resultId = $resultId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getResultId(): ResultId
    {
        return $this->resultId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
