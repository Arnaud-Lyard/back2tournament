<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Model;

final class UpdatePlayerCommand
{
    private string $playerId;

    private string $battletag;

    public function getPlayerId(): string
    {
        return $this->playerId;
    }

    public function setPlayerId(string $playerId): void
    {
        $this->playerId = $playerId;
    }

    public function getBattletag(): string
    {
        return $this->battletag;
    }

    public function setBattletag(string $battletag): void
    {
        $this->battletag = $battletag;
    }
}
