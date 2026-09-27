<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

/**
 * The clan tags shown next to player profiles and teams.
 */
interface ClanTagProviderInterface
{
    /**
     * The clan each player profile is an active member of, keyed by player
     * id. A profile in no clan, or only invited to one, is left out.
     *
     * @param list<string> $playerIds
     *
     * @return array<string, array{id: string, tag: string}>
     */
    public function clansOfPlayers(array $playerIds): array;

    /**
     * The tag of each clan, keyed by clan id; an unknown id is left out.
     *
     * @param list<string> $clanIds
     *
     * @return array<string, string>
     */
    public function tagsOfClans(array $clanIds): array;
}
