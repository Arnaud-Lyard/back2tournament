<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class ConfirmFightResultsCommand
{
    private string $fightId;

    private string $user;

    public function __construct(string $fightId, string $user)
    {
        $this->fightId = $fightId;
        $this->user = $user;
    }

    public function getFightId(): string
    {
        return $this->fightId;
    }

    public function getUser(): string
    {
        return $this->user;
    }
}
