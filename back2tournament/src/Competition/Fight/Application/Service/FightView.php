<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Enum\ResultStatus;

/**
 * How a fight reads in the API: the fight, where it stands as a whole, and its
 * two sides, each named and carrying its own result.
 */
final class FightView
{
    /**
     * @param list<Result>                                                          $results   a side with no result reads as pending, 0 points
     * @param array<string, array{type: string, reference: string, name: ?string}> $described the sides, keyed by competitor id
     * @param list<string>                                                          $represented the competitors the caller speaks for
     *
     * @return array<string, mixed>
     */
    public static function of(Fight $fight, array $results, array $described, array $represented = []): array
    {
        $byCompetitor = [];
        foreach ($results as $result) {
            $byCompetitor[$result->getCompetitor()->getValue()] = $result;
        }

        $sides = [];
        $statuses = [];
        $winner = null;
        foreach ([$fight->getCompetitorOne()->getValue(), $fight->getCompetitorTwo()->getValue()] as $competitor) {
            $result = $byCompetitor[$competitor] ?? null;
            $status = $result?->getStatus() ?? ResultStatus::PENDING;
            $statuses[] = $status;

            if (ResultStatus::WIN === $status) {
                $winner = ['value' => $competitor];
            }

            $sides[] = [
                'competitor' => ['value' => $competitor],
                'type' => $described[$competitor]['type'] ?? null,
                'reference' => isset($described[$competitor]) ? ['value' => $described[$competitor]['reference']] : null,
                'name' => $described[$competitor]['name'] ?? null,
                'score' => $result?->getScore() ?? 0,
                'status' => $status->value,
                'reportedStatus' => $result?->getReportedStatus()?->value,
            ];
        }

        $mySide = null;
        foreach ([$fight->getCompetitorOne()->getValue(), $fight->getCompetitorTwo()->getValue()] as $competitor) {
            if (\in_array($competitor, $represented, true)) {
                $mySide = ['value' => $competitor];
                break;
            }
        }

        return [
            'id' => ['value' => $fight->getId()->getValue()],
            'game' => ['value' => $fight->getGame()->getValue()],
            'teamSize' => $fight->getTeamSize(),
            'tournament' => null === $fight->getTournament() ? null : ['value' => $fight->getTournament()->getValue()],
            'status' => self::status($statuses),
            'declaredBy' => null === $fight->getDeclaredBy() ? null : ['value' => $fight->getDeclaredBy()->getValue()],
            'winner' => $winner,
            'mySide' => $mySide,
            'sides' => $sides,
            'createdAt' => $fight->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $fight->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param list<ResultStatus> $statuses
     */
    private static function status(array $statuses): string
    {
        if (\in_array(ResultStatus::REPORTING, $statuses, true)) {
            return 'reporting';
        }

        return \in_array(ResultStatus::PENDING, $statuses, true) ? 'pending' : 'finished';
    }
}
