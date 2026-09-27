<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Model;

/**
 * Counts a settled fight in the rankings of its two sides.
 */
final class RateFightCommand
{
    private string $fightId;

    public function __construct(string $fightId)
    {
        $this->fightId = $fightId;
    }

    public function getFightId(): string
    {
        return $this->fightId;
    }
}
