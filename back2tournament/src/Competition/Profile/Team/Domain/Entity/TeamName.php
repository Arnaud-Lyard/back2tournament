<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Domain\Entity;

use App\Shared\ValueObject\NameValueObject;

final class TeamName extends NameValueObject
{
    protected const MAX_LENGTH = 50;
}
