<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Domain\Event;

use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class GameUpdatedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected GameId $gameId;

    public function __construct(GameId $gameId)
    {
        $this->gameId = $gameId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getGameId(): GameId
    {
        return $this->gameId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
