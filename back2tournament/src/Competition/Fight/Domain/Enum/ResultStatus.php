<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Enum;

enum ResultStatus: string
{
    case WIN = 'win';
    case LOSS = 'loss';
    case DRAW = 'draw';
    case REPORTING = 'reporting';
    case PENDING = 'pending';
}
