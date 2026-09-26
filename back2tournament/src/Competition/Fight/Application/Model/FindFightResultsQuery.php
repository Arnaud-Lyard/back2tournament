<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class FindFightResultsQuery
{
    private string $fightId;

    private string $gameId;

    public function __construct(string $fightId, string $gameId)
    {
        $this->fightId = $fightId;
        $this->gameId = $gameId;
    }

    public function getFightId(): string
    {
        return $this->fightId;
    }

    public function getGameId(): string
    {
        return $this->gameId;
    }
}
