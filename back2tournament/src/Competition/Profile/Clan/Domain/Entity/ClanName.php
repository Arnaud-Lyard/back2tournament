<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Domain\Entity;

use App\Shared\ValueObject\NameValueObject;

final class ClanName extends NameValueObject
{
    protected const MAX_LENGTH = 50;
}
