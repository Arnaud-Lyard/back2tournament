<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Enum;

/**
 * Who a ranking ranks: player profiles on their 1v1 fights, clans on the
 * fights of their teams.
 */
enum RankingSubject: string
{
    case PLAYER = 'player';
    case CLAN = 'clan';
}
