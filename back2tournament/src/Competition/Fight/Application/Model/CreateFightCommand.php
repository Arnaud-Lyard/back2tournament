<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class CreateFightCommand
{
    private bool $betweenTeams;

    private string $sideOne;

    private string $sideTwo;

    public function __construct(bool $betweenTeams, string $sideOne, string $sideTwo)
    {
        $this->betweenTeams = $betweenTeams;
        $this->sideOne = $sideOne;
        $this->sideTwo = $sideTwo;
    }

    public function isBetweenTeams(): bool
    {
        return $this->betweenTeams;
    }

    public function getSideOne(): string
    {
        return $this->sideOne;
    }

    public function getSideTwo(): string
    {
        return $this->sideTwo;
    }
}
