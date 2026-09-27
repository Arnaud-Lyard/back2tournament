<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Enum;

/**
 * How a settled fight ended, seen from its first side.
 */
enum FightOutcome
{
    case SIDE_ONE_WON;
    case SIDE_TWO_WON;
    case DRAW;

    /**
     * What the first side scored, as Elo counts it: 1 for a win, 0.5 for a
     * draw, 0 for a loss. The second side scored what is left of 1.
     */
    public function scoreOfSideOne(): float
    {
        return match ($this) {
            self::SIDE_ONE_WON => 1.0,
            self::SIDE_TWO_WON => 0.0,
            self::DRAW => 0.5,
        };
    }
}
