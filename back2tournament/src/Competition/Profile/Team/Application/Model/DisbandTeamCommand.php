<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Model;

final class DisbandTeamCommand
{
    private string $teamId;

    public function __construct(string $teamId)
    {
        $this->teamId = $teamId;
    }

    public function getTeamId(): string
    {
        return $this->teamId;
    }
}
