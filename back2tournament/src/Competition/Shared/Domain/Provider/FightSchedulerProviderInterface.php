<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

use App\Competition\Fight\Domain\Entity\Fight;

interface FightSchedulerProviderInterface
{
    public function schedule(
        string $competitorOne,
        string $competitorTwo,
        string $gameId,
        int $teamSize,
        ?string $tournamentId = null,
    ): Fight;
}
