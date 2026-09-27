<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Model;

final class CancelTournamentCommand
{
    private string $tournamentId;

    public function __construct(string $tournamentId)
    {
        $this->tournamentId = $tournamentId;
    }

    public function getTournamentId(): string
    {
        return $this->tournamentId;
    }
}
