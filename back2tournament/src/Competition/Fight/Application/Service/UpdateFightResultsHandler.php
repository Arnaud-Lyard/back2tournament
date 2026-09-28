<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Competition\Fight\Application\Model\UpdateFightResultsCommand;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\Score;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class UpdateFightResultsHandler
{
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(UpdateFightResultsCommand $updateFightResultsCommand): string
    {
        $fightId = new FightId($updateFightResultsCommand->getFightId());
        $score = new Score($updateFightResultsCommand->getScore());
        $opponentScore = new Score($updateFightResultsCommand->getOpponentScore());

        $fight = $this->fightRepository->findOneBy(['id' => $fightId->getValue()]);
        if (!$fight instanceof Fight) {
            throw new NotFoundException('fight not found');
        }

        $represented = $this->competitorRegistryProvider->representedBy($updateFightResultsCommand->getUser());
        $side = $fight->sideAmong($represented);
        if (null === $side) {
            throw new PermissionDeniedException('you do not take part in this fight');
        }
        $opponent = $fight->opponentOf($side);

        $declaring = $this->resultRepository->findOneBy(['fight' => $fightId->getValue(), 'competitor' => $side->getValue()]);
        $opposing = $this->resultRepository->findOneBy(['fight' => $fightId->getValue(), 'competitor' => $opponent->getValue()]);
        if (!$declaring instanceof Result || !$opposing instanceof Result) {
            throw new NotFoundException('result not found');
        }

        Fight::declareOutcome($fight, $side, $declaring, $score, $opposing, $opponentScore);

        $this->resultRepository->save($declaring);
        $this->resultRepository->save($opposing);
        $this->fightRepository->save($fight);

        foreach ($fight->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return json_encode(
            $this->normalizeFight($fight, [$declaring, $opposing], $this->competitorRegistryProvider->describe([$side->getValue(), $opponent->getValue()]), $represented),
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param list<Result> $results
     * @param array<string, array{type: string, reference: string, name: ?string, tag: ?string}> $described
     * @param list<string> $represented
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
                'tag' => $described[$competitor]['tag'] ?? null,
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
