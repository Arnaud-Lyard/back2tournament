<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Model;

final class AdmitClanMemberCommand
{
    private string $clanId;

    private string $playerId;

    public function __construct(string $clanId, string $playerId)
    {
        $this->clanId = $clanId;
        $this->playerId = $playerId;
    }

    public function getClanId(): string
    {
        return $this->clanId;
    }

    public function getPlayerId(): string
    {
        return $this->playerId;
    }
}
