<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class FindPendingPlayerResultsQuery
{
    private string $gameId;

    public function __construct(string $gameId)
    {
        $this->gameId = $gameId;
    }

    public function getGameId(): string
    {
        return $this->gameId;
    }
}
