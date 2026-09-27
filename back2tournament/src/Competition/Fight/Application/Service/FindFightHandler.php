<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Model\FindFightQuery;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class FindFightHandler
{
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private CurrentUserProviderInterface $currentUserProvider;

    public function __construct(
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        CurrentUserProviderInterface $currentUserProvider,
    ) {
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->currentUserProvider = $currentUserProvider;
    }

    public function __invoke(FindFightQuery $findFightQuery): string
    {
        $fightId = new FightId($findFightQuery->getFightId());

        $fight = $this->fightRepository->findOneBy(['id' => $fightId->getValue()]);
        if (!$fight instanceof Fight) {
            throw new NotFoundException('fight not found');
        }

        return json_encode(
            $this->normalizeFight(
                $fight,
                $this->resultRepository->findBy(['fight' => $fightId->getValue()]),
                $this->competitorRegistryProvider->describe([$fight->getCompetitorOne()->getValue(), $fight->getCompetitorTwo()->getValue()]),
                $this->competitorRegistryProvider->representedBy((string) $this->currentUserProvider->getUser()->getId()),
            ),
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * The fight, where it stands as a whole, and its two sides, each named and
     * carrying its own result.
     *
     * @param list<Result>                                                         $results     a side with no result reads as pending, 0 points
     * @param array<string, array{type: string, reference: string, name: ?string}> $described   the sides, keyed by competitor id
     * @param list<string>                                                         $represented the competitors the caller speaks for
     *
     * @return array<string, mixed>
     */
    private function normalizeFight(Fight $fight, array $results, array $described, array $represented): array
    {
        $byCompetitor = [];
        foreach ($results as $result) {
            $byCompetitor[$result->getCompetitor()->getValue()] = $result;
        }

        $sides = [];
        $statuses = [];
        $winner = null;
        $mySide = null;
        foreach ([$fight->getCompetitorOne()->getValue(), $fight->getCompetitorTwo()->getValue()] as $competitor) {
            $result = $byCompetitor[$competitor] ?? null;
            $status = $result?->getStatus() ?? ResultStatus::PENDING;
            $statuses[] = $status;

            if (ResultStatus::WIN === $status) {
                $winner = ['value' => $competitor];
            }

            if (null === $mySide && \in_array($competitor, $represented, true)) {
                $mySide = ['value' => $competitor];
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

        $status = 'finished';
        if (\in_array(ResultStatus::REPORTING, $statuses, true)) {
            $status = 'reporting';
        } elseif (\in_array(ResultStatus::PENDING, $statuses, true)) {
            $status = 'pending';
        }

        return [
            'id' => ['value' => $fight->getId()->getValue()],
            'game' => ['value' => $fight->getGame()->getValue()],
            'teamSize' => $fight->getTeamSize(),
            'tournament' => null === $fight->getTournament() ? null : ['value' => $fight->getTournament()->getValue()],
            'status' => $status,
            'declaredBy' => null === $fight->getDeclaredBy() ? null : ['value' => $fight->getDeclaredBy()->getValue()],
            'arbitrated' => $fight->isArbitrated(),
            'winner' => $winner,
            'mySide' => $mySide,
            'sides' => $sides,
            'createdAt' => $fight->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $fight->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
