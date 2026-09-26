<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Domain\Enum;

enum TournamentStatus: string
{
    case UPCOMING = 'upcoming';
    case ONGOING = 'ongoing';
    case FINISHED = 'finished';
    case CANCELLED = 'cancelled';
}
