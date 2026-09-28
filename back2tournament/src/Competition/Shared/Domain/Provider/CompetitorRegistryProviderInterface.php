<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

interface CompetitorRegistryProviderInterface
{
    public function enlistPlayer(string $playerId): string;

    public function enlistTeam(string $teamId): string;

    /** @return list<string> */
    public function representedBy(string $userId): array;

    /** @return list<string> */
    public function competitorsOfPlayer(string $playerId): array;

    /**
     * @param list<string> $competitorIds
     *
     * @return array<string, array{type: string, reference: string, name: ?string, tag: ?string}>
     */
    public function describe(array $competitorIds): array;

    /**
     * @param list<string> $competitorIds
     *
     * @return array<string, array{players: list<string>, clan: ?string}>
     */
    public function lineups(array $competitorIds): array;

    /** @return list<string> */
    public function named(string $search, ?string $gameId = null): array;

    public function teamHasCompeted(string $teamId): bool;
}
