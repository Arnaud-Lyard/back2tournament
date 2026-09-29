<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Enum;

enum RankingSubject: string
{
    case PLAYER = 'player';
    case CLAN = 'clan';
}
