<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Domain\Entity;

use App\Shared\ValueObject\NameValueObject;

final class TournamentName extends NameValueObject
{
    protected const MAX_LENGTH = 100;
}
