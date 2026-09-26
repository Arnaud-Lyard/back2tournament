<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class DeclareFightResultsCommand
{
    private string $fightId;

    private int $score;

    private int $opponentScore;

    public function __construct(string $fightId, int $score, int $opponentScore)
    {
        $this->fightId = $fightId;
        $this->score = $score;
        $this->opponentScore = $opponentScore;
    }

    public function getFightId(): string
    {
        return $this->fightId;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getOpponentScore(): int
    {
        return $this->opponentScore;
    }
}
