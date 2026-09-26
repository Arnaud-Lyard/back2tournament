<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

class OnUpdateFightResultsEvent extends Event
{
    private string $fight;

    private int $score;

    private int $opponentScore;

    private string $updatedFight;

    public function __construct(
        string $fight,
        int $score,
        int $opponentScore,
    ) {
        $this->fight = $fight;
        $this->score = $score;
        $this->opponentScore = $opponentScore;
    }

    public function getFight(): string
    {
        return $this->fight;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getOpponentScore(): int
    {
        return $this->opponentScore;
    }

    /**
     * The fight once declared, as the handler serialized it, handed back to the controller.
     */
    public function getUpdatedFight(): string
    {
        return $this->updatedFight;
    }

    public function setUpdatedFight(string $updatedFight): void
    {
        $this->updatedFight = $updatedFight;
    }
}
