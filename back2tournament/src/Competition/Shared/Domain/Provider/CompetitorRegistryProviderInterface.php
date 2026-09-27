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
     * @param list<string> $competitorIds
     *
     * @return array<string, array{type: string, reference: string, name: ?string}> keyed by competitor id; an unknown id is left out
     */
    public function describe(array $competitorIds): array;

    /**
     * Whether the team was ever enlisted, in a fight or a tournament.
     */
    public function teamHasCompeted(string $teamId): bool;
}
