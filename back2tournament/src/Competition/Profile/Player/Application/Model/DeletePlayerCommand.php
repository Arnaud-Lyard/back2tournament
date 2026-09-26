<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Model;

final class DeletePlayerCommand
{
    private string $playerId;

    public function getPlayerId(): string
    {
        return $this->playerId;
    }

    public function setPlayerId(string $playerId): void
    {
        $this->playerId = $playerId;
    }
}
