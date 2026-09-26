<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class UpdateFightResultsCommand
{
    private string $fightId;

    private string $user;

    private int $score;

    private int $opponentScore;

    public function __construct(string $fightId, string $user, int $score, int $opponentScore)
    {
        $this->fightId = $fightId;
        $this->user = $user;
        $this->score = $score;
        $this->opponentScore = $opponentScore;
    }

    public function getFightId(): string
    {
        return $this->fightId;
    }

    /**
     * The caller, as the User context verified it.
     */
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
}
