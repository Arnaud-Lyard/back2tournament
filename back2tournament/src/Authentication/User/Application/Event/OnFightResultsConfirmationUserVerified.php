<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Event;

class OnFightResultsConfirmationUserVerified
{
    private string $fight;

    private string $user;

    private string $game;


    public function __construct(
        string $fight,
        string $user,
        string $game,
    ) {
        $this->fight = $fight;
        $this->user = $user;
        $this->game = $game;
    }

    public function getFight(): string
    {
        return $this->fight;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getGame(): string
    {
        return $this->game;
    }

}
