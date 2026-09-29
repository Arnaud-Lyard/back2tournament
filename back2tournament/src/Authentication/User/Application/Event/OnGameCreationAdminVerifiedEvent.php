<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnGameCreationAdminVerifiedEvent extends Event
{
    private string $title;
    private string $user;

    /** @var list<int> */
    private array $teamSizes;

    private string $createdGame;

    /** @param list<int> $teamSizes */
    public function __construct(string $title, string $user, array $teamSizes)
    {
        $this->title = $title;
        $this->user = $user;
        $this->teamSizes = $teamSizes;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    /** @return list<int> */
    public function getTeamSizes(): array
    {
        return $this->teamSizes;
    }

    public function getCreatedGame(): string
    {
        return $this->createdGame;
    }

    public function setCreatedGame(string $createdGame): void
    {
        $this->createdGame = $createdGame;
    }
}
