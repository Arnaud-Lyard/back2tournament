<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

interface CompetitorIdProviderInterface
{
    public function byUserAndGame(string $userId, string $gameId): string;

    public function takesPartInFights(string $playerId): bool;
}
