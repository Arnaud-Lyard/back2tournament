<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnFightCompetitorResolvedEvent extends Event
{
    private string $fight;

    private string $user;

    private string $competitorOne;

    private string $competitorTwo;

    private string $competitorOneStatus;

    private int $competitorOneScore;

    private string $competitorTwoStatus;

    private int $competitorTwoScore;

    public function __construct(
        string $fight,
        string $user,
        string $competitorOne,
        string $competitorTwo,
        string $competitorOneStatus,
        int $competitorOneScore,
        string $competitorTwoStatus,
        int $competitorTwoScore,
    ) {
        $this->fight = $fight;
        $this->user = $user;
        $this->competitorOne = $competitorOne;
        $this->competitorTwo = $competitorTwo;
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

    public function getCompetitorOne(): string
    {
        return $this->competitorOne;
    }

    public function getCompetitorTwo(): string
    {
        return $this->competitorTwo;
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
