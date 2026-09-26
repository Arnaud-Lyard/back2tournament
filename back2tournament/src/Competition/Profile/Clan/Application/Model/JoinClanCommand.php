<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Model;

final class JoinClanCommand
{
    private string $clanId;

    public function __construct(string $clanId)
    {
        $this->clanId = $clanId;
    }

    public function getClanId(): string
    {
        return $this->clanId;
    }
}
