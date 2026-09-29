<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Model;

final class UpdateGameCommand
{
    private string $gameId;

    private ?string $title;

    /** @var (list<int> | null) */
    private ?array $teamSizes;

    /** @param (list<int> | null) $teamSizes */
    public function __construct(string $gameId, ?string $title, ?array $teamSizes)
    {
        $this->gameId = $gameId;
        $this->title = $title;
        $this->teamSizes = $teamSizes;
    }

    public function getGameId(): string
    {
        return $this->gameId;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /** @return (list<int> | null) */
    public function getTeamSizes(): ?array
    {
        return $this->teamSizes;
    }
}
