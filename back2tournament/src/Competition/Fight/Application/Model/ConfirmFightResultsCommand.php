<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class ConfirmFightResultsCommand
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
