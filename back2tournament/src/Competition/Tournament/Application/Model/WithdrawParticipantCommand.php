<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Model;

final class WithdrawParticipantCommand
{
    private string $tournamentId;

    private string $participantId;

    public function __construct(string $tournamentId, string $participantId)
    {
        $this->tournamentId = $tournamentId;
        $this->participantId = $participantId;
    }

    public function getTournamentId(): string
    {
        return $this->tournamentId;
    }

    public function getParticipantId(): string
    {
        return $this->participantId;
    }
}
