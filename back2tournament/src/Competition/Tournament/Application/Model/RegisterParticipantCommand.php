<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Model;

final class RegisterParticipantCommand
{
    private string $tournamentId;

    private ?string $playerId;

    private ?string $teamId;

    public function __construct(string $tournamentId, ?string $playerId, ?string $teamId)
    {
        $this->tournamentId = $tournamentId;
        $this->playerId = $playerId;
        $this->teamId = $teamId;
    }

    public function getTournamentId(): string
    {
        return $this->tournamentId;
    }

    public function getPlayerId(): ?string
    {
        return $this->playerId;
    }

    public function getTeamId(): ?string
    {
        return $this->teamId;
    }
}
