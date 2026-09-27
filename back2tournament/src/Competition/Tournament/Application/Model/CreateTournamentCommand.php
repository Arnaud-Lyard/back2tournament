<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Model;

final class CreateTournamentCommand
{
    private string $name;

    private string $game;

    private int $teamSize;

    private int $capacity;

    private string $startsAt;

    public function __construct(string $name, string $game, int $teamSize, int $capacity, string $startsAt)
    {
        $this->name = $name;
        $this->game = $game;
        $this->teamSize = $teamSize;
        $this->capacity = $capacity;
        $this->startsAt = $startsAt;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGame(): string
    {
        return $this->game;
    }

    public function getTeamSize(): int
    {
        return $this->teamSize;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getStartsAt(): string
    {
        return $this->startsAt;
    }
}
