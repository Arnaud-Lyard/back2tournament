<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

/**
 * The competitors behind player profiles and teams: who they are, who speaks
 * for them, and enlisting them the first time they compete.
 */
interface CompetitorRegistryProviderInterface
{
    /**
     * The competitor a player profile competes as in 1v1, created on first use.
     */
    public function enlistPlayer(string $playerId): string;

    /**
     * The competitor a team competes as, created on first use.
     */
    public function enlistTeam(string $teamId): string;

    /**
     * Every competitor the user speaks for: their own player profiles, and the
     * teams one of those profiles leads. Only competitors that already exist.
     *
     * @return list<string>
     */
    public function representedBy(string $userId): array;

    /**
     * Every competitor a player profile has played as: itself in 1v1, and the
     * teams whose lineup it belongs to. Only competitors that already exist.
     *
     * @return list<string>
     */
    public function competitorsOfPlayer(string $playerId): array;

    /**
     * The competitors of a clan's teams. Only the teams that already competed.
     *
     * @return list<string>
     */
    public function competitorsOfClan(string $clanId): array;

    /**
     * @param list<string> $competitorIds
     *
     * `tag` is the tag of the clan the side plays for: the clan of the
     * player profile, or of the team; null for a profile in no clan.
     *
     * @return array<string, array{type: string, reference: string, name: ?string, tag: ?string}> keyed by competitor id; an unknown id is left out
     */
    public function describe(array $competitorIds): array;

    /**
     * What a competitor's results count for in the rankings: the player
     * profile it is, or the clan its team plays for.
     *
     * @param list<string> $competitorIds
     *
     * @return array<string, array{type: 'player'|'clan', id: string}> keyed by competitor id; an unknown id is left out
     */
    public function rankedAs(array $competitorIds): array;

    /**
     * The competitors whose name holds $search: player profiles by battletag,
     * teams by name, in one game or in all of them. Only competitors that
     * already exist.
     *
     * @return list<string>
     */
    public function named(string $search, ?string $gameId = null): array;

    /**
     * Whether the team was ever enlisted, in a fight or a tournament.
     */
    public function teamHasCompeted(string $teamId): bool;
}
