<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

class OnUpdateFightResultsEvent extends Event
{
    private string $fight;

    private string $game;

    private string $competitorOneStatus;

    private int $competitorOneScore;

    private string $competitorTwoStatus;

    private int $competitorTwoScore;

    public function __construct(
        string $fight,
        string $game,
        string $competitorOneStatus,
        int $competitorOneScore,
        string $competitorTwoStatus,
        int $competitorTwoScore
    ){
        $this->fight = $fight;
        $this->game = $game;
        $this->competitorOneStatus = $competitorOneStatus;
        $this->competitorOneScore = $competitorOneScore;
        $this->competitorTwoStatus = $competitorTwoStatus;
        $this->competitorTwoScore = $competitorTwoScore;
    }

    public function getFight(): string
    {
        return $this->fight;
    }

    public function getGame(): string
    {
        return $this->game;
    }

    public function getCompetitorOneStatus(): string
    {
        return $this->competitorOneStatus;
    }

    public function getCompetitorOneScore(): int
    {
        return $this->competitorOneScore;
    }

    public function getCompetitorTwoStatus(): string
    {
        return $this->competitorTwoStatus;
    }

    public function getCompetitorTwoScore(): int
    {
        return $this->competitorTwoScore;
    }
}
