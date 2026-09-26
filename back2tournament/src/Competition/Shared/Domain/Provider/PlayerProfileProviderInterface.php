<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

interface PlayerProfileProviderInterface
{
    /**
     *
     * @return list<array{id: string, battletag: string, game: string}>
     */
    public function byUser(string $userId): array;
}
