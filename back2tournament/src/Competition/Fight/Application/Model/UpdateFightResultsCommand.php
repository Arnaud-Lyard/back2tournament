<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class UpdateFightResultsCommand
{
    private string $fight;

    private string $user;

    private string $competitorOne;

    private string $competitorTwo;

    private string $competitorOneStatus;

    private int $competitorOneScore;

    private string $competitorTwoStatus;

    private int $competitorTwoScore;

    public function getFight(): string
    {
        return $this->fight;
    }

    public function setFight(string $fight): void
    {
        $this->fight = $fight;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    public function getCompetitorOne(): string
    {
        return $this->competitorOne;
    }

    public function setCompetitorOne(string $competitorOne): void
    {
        $this->competitorOne = $competitorOne;
    }

    public function getCompetitorTwo(): string
    {
        return $this->competitorTwo;
    }

    public function setCompetitorTwo(string $competitorTwo): void
    {
        $this->competitorTwo = $competitorTwo;
    }

    public function getCompetitorOneStatus(): string
    {
        return $this->competitorOneStatus;
    }

    public function setCompetitorOneStatus(string $competitorOneStatus): void
    {
        $this->competitorOneStatus = $competitorOneStatus;
    }

    public function getCompetitorOneScore(): int
    {
        return $this->competitorOneScore;
    }

    public function setCompetitorOneScore(int $competitorOneScore): void
    {
        $this->competitorOneScore = $competitorOneScore;
    }

    public function getCompetitorTwoStatus(): string
    {
        return $this->competitorTwoStatus;
    }

    public function setCompetitorTwoStatus(string $competitorTwoStatus): void
    {
        $this->competitorTwoStatus = $competitorTwoStatus;
    }

    public function getCompetitorTwoScore(): int
    {
        return $this->competitorTwoScore;
    }

    public function setCompetitorTwoScore(int $competitorTwoScore): void
    {
        $this->competitorTwoScore = $competitorTwoScore;
    }
}
