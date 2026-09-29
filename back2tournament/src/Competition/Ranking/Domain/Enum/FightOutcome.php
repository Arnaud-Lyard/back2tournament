<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Enum;

enum FightOutcome
{
    case SIDE_ONE_WON;
    case SIDE_TWO_WON;
    case DRAW;

    public function scoreOfSideOne(): float
    {
        return match ($this) {
            self::SIDE_ONE_WON => 1.0,
            self::SIDE_TWO_WON => 0.0,
            self::DRAW => 0.5,
        };
    }
}
