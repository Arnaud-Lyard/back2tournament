<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Domain\Enum;

enum ClanMemberStatus: string
{
    case INVITED = 'invited';
    case REQUESTED = 'requested';
    case ACTIVE = 'active';
}
