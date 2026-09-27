<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

use App\Competition\Fight\Domain\Entity\Fight;

/**
 * Opens fights, for a challenge between two sides as well as for a tournament bracket.
 */
interface FightSchedulerProviderInterface
{
    /**
     * A fight between two competitors, with a pending result for each side.
     */
    public function schedule(
        string $competitorOne,
        string $competitorTwo,
        string $gameId,
        int $teamSize,
        ?string $tournamentId = null,
    ): Fight;
}
