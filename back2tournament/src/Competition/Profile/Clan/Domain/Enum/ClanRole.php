<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Domain\Enum;

enum ClanRole: string
{
    case LEADER = 'leader';
    case MEMBER = 'member';
}
