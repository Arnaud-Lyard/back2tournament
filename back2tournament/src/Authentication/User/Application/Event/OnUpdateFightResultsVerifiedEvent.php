<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Event;

class OnUpdateFightResultsVerifiedEvent
{
    private string $fight;

    private string $user;

    private string $game;

    private string $competitorOneStatus;

    private int $competitorOneScore;

    private string $competitorTwoStatus;

    private int $competitorTwoScore;

    public function __construct(
        string $fight,
        string $user,
        string $game,
        string $competitorOneStatus,
        int $competitorOneScore,
        string $competitorTwoStatus,
        int $competitorTwoScore,
    ) {
        $this->fight = $fight;
        $this->user = $user;
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

    public function getUser(): string
    {
        return $this->user;
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
