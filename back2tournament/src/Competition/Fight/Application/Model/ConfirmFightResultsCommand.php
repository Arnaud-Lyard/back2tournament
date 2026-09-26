<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class ConfirmFightResultsCommand
{
    private string $fight;

    private string $game;

    private string $competitorOne;

    private string $competitorTwo;

    public function getFight(): string
    {
        return $this->fight;
    }

    public function setFight(string $fight): void
    {
        $this->fight = $fight;
    }

    public function getGame(): string
    {
        return $this->game;
    }

    public function setGame(string $game): void
    {
        $this->game = $game;
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
}
