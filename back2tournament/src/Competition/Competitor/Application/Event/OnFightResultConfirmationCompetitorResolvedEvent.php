<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnFightResultConfirmationCompetitorResolvedEvent extends Event
{
    private string $fight;

    private string $user;

    private string $game;

    private string $competitorOne;

    private string $competitorTwo;

    public function __construct(
        string $fight,
        string $user,
        string $game,
        string $competitorOne,
        string $competitorTwo,
    ) {
        $this->fight = $fight;
        $this->user = $user;
        $this->game = $game;
        $this->competitorOne = $competitorOne;
        $this->competitorTwo = $competitorTwo;
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

    public function getCompetitorOne(): string
    {
        return $this->competitorOne;
    }

    public function getCompetitorTwo(): string
    {
        return $this->competitorTwo;
    }
}
