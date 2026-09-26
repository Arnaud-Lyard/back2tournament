<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class CreateFightCommand
{
    private string $competitorOne;

    private string $competitorTwo;

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
