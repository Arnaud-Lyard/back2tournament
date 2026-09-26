<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Domain\Enum;

enum CompetitorType: string
{
    case PLAYER = 'player';
    case TEAM = 'team';
}
