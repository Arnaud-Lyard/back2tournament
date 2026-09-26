<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Event;

class OnUpdateFightResultsVerifiedEvent
{
    private string $fight;

    private string $user;

    private int $score;

    private int $opponentScore;

    private string $updatedFight;

    public function __construct(
        string $fight,
        string $user,
        int $score,
        int $opponentScore,
    ) {
        $this->fight = $fight;
        $this->user = $user;
        $this->score = $score;
        $this->opponentScore = $opponentScore;
    }

    public function getFight(): string
    {
        return $this->fight;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getOpponentScore(): int
    {
        return $this->opponentScore;
    }

    public function getUpdatedFight(): string
    {
        return $this->updatedFight;
    }

    public function setUpdatedFight(string $updatedFight): void
    {
        $this->updatedFight = $updatedFight;
    }
}
