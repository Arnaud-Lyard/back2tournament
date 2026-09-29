<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

interface ClanTagProviderInterface
{
    /**
     * @param list<string> $playerIds
     *
     * @return array<string, array{id: string, tag: string}>
     */
    public function clansOfPlayers(array $playerIds): array;

    /**
     * @param list<string> $clanIds
     *
     * @return array<string, string>
     */
    public function tagsOfClans(array $clanIds): array;
}
