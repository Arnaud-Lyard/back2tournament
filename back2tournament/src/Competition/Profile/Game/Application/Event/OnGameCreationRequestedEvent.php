<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnGameCreationRequestedEvent extends Event
{
    private string $title;

    /**
     * @var list<int>
     */
    private array $teamSizes;

    private string $createdGame;

    /**
     * @param list<int> $teamSizes
     */
    public function __construct(string $title, array $teamSizes)
    {
        $this->title = $title;
        $this->teamSizes = $teamSizes;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return list<int>
     */
    public function getTeamSizes(): array
    {
        return $this->teamSizes;
    }

    /**
     * The created game, as the handler serialized it, handed back to the controller.
     */
    public function getCreatedGame(): string
    {
        return $this->createdGame;
    }

    public function setCreatedGame(string $createdGame): void
    {
        $this->createdGame = $createdGame;
    }
}
