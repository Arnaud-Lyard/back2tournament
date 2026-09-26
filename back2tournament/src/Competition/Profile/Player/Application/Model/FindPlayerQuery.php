<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Model;

final class FindPlayerQuery
{
    private string $playerId;

    public function __construct(string $playerId)
    {
        $this->playerId = $playerId;
    }

    public function getPlayerId(): string
    {
        return $this->playerId;
    }
}
