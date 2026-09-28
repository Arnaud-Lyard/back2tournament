<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Model;

final class RateFightCommand
{
    private string $fightId;

    public function __construct(string $fightId)
    {
        $this->fightId = $fightId;
    }

    public function getFightId(): string
    {
        return $this->fightId;
    }
}
