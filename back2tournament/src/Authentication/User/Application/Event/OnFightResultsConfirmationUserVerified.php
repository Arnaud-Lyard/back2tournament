<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Event;

class OnFightResultsConfirmationUserVerified
{
    private string $fight;

    private string $user;

    private string $confirmedFight;

    public function __construct(
        string $fight,
        string $user,
    ) {
        $this->fight = $fight;
        $this->user = $user;
    }

    public function getFight(): string
    {
        return $this->fight;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getConfirmedFight(): string
    {
        return $this->confirmedFight;
    }

    public function setConfirmedFight(string $confirmedFight): void
    {
        $this->confirmedFight = $confirmedFight;
    }
}
