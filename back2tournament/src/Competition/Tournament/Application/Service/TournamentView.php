<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Tournament\Domain\Entity\Matchup;
use App\Competition\Tournament\Domain\Entity\Participant;
use App\Competition\Tournament\Domain\Entity\Tournament;

/**
 * How a tournament reads in the API: competitors are named by battletag or team
 * name, through the descriptions CompetitorRegistryInterface::describe() gives.
 */
final class TournamentView
{
    /**
     * @param array<string, array{type: string, reference: string, name: ?string}> $described
     *
     * @return array<string, mixed>
     */
    public static function summary(Tournament $tournament, int $participants, array $described): array
    {
        return [
            'id' => ['value' => $tournament->getId()->getValue()],
            'name' => $tournament->getName(),
            'game' => ['value' => $tournament->getGame()->getValue()],
            'teamSize' => $tournament->getTeamSize(),
            'capacity' => $tournament->getCapacity(),
            'participantCount' => $participants,
            'status' => $tournament->getStatus()->value,
            'organizer' => ['value' => $tournament->getOrganizer()->getValue()],
            'startsAt' => $tournament->getStartsAt()->format(\DateTimeInterface::ATOM),
            'winner' => self::side($tournament->getWinner(), $described),
            'createdAt' => $tournament->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $tournament->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param list<Participant>                                                     $participants
     * @param list<Matchup>                                                         $bracket
     * @param array<string, array{type: string, reference: string, name: ?string}> $described
     *
     * @return array<string, mixed>
     */
    public static function detail(Tournament $tournament, array $participants, array $bracket, array $described): array
    {
        usort($participants, static fn (Participant $one, Participant $two): int => $one->getSeed() <=> $two->getSeed());
        usort($bracket, static fn (Matchup $one, Matchup $two): int => [$one->getRound(), $one->getPosition()] <=> [$two->getRound(), $two->getPosition()]);

        $view = self::summary($tournament, \count($participants), $described);
        $view['participants'] = array_map(
            static fn (Participant $participant): array => self::participant($participant, $described),
            $participants,
        );
        $view['rounds'] = [] === $bracket ? 0 : max(array_map(static fn (Matchup $matchup): int => $matchup->getRound(), $bracket));
        $view['matchups'] = array_map(static fn (Matchup $matchup): array => [
            'id' => ['value' => $matchup->getId()->getValue()],
            'round' => $matchup->getRound(),
            'position' => $matchup->getPosition(),
            'sides' => [
                self::side($matchup->getCompetitorOne(), $described),
                self::side($matchup->getCompetitorTwo(), $described),
            ],
            'fight' => null === $matchup->getFight() ? null : ['value' => $matchup->getFight()->getValue()],
            'winner' => null === $matchup->getWinner() ? null : ['value' => $matchup->getWinner()->getValue()],
        ], $bracket);

        return $view;
    }

    /**
     * @param array<string, array{type: string, reference: string, name: ?string}> $described
     *
     * @return array<string, mixed>
     */
    public static function participant(Participant $participant, array $described): array
    {
        return [
            'id' => ['value' => $participant->getId()->getValue()],
            'tournament' => ['value' => $participant->getTournament()->getValue()],
            'seed' => $participant->getSeed(),
            'createdAt' => $participant->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ] + self::side($participant->getCompetitor(), $described);
    }

    /**
     * @param array<string, array{type: string, reference: string, name: ?string}> $described
     *
     * @return array<string, mixed>|null
     */
    private static function side(?CompetitorId $competitor, array $described): ?array
    {
        if (null === $competitor) {
            return null;
        }

        $description = $described[$competitor->getValue()] ?? null;

        return [
            'competitor' => ['value' => $competitor->getValue()],
            'type' => $description['type'] ?? null,
            'reference' => null === $description ? null : ['value' => $description['reference']],
            'name' => $description['name'] ?? null,
        ];
    }
}
