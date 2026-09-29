<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class ChangeFightStatusCommand
{
    private string $fightId;

    private string $status;

    /** @var (array<string, mixed> | null) */
    private ?array $scores;

    /** @param (array<string, mixed> | null) $scores */
    public function __construct(string $fightId, string $status, ?array $scores)
    {
        $this->fightId = $fightId;
        $this->status = $status;
        $this->scores = $scores;
    }

    public function getFightId(): string
    {
        return $this->fightId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /** @return (array<string, mixed> | null) */
    public function getScores(): ?array
    {
        return $this->scores;
    }
}
