<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Model;

final class AdvanceTournamentCommand
{
    private string $fightId;

    private ?string $winner;

    public function __construct(string $fightId, ?string $winner)
    {
        $this->fightId = $fightId;
        $this->winner = $winner;
    }

    public function getFightId(): string
    {
        return $this->fightId;
    }

    public function getWinner(): ?string
    {
        return $this->winner;
    }
}
