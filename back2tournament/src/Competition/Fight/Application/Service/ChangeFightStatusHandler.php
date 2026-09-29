<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Model\ChangeFightStatusCommand;
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
use App\Shared\Exception\ValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class ChangeFightStatusHandler
{
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(ChangeFightStatusCommand $changeFightStatusCommand): string
    {
        $fightId = new FightId($changeFightStatusCommand->getFightId());
        $status = $changeFightStatusCommand->getStatus();
        if (!\in_array($status, ['finished', 'pending'], true)) {
            throw new ValidationException('status must be finished or pending');
        }
        $scores = 'finished' === $status ? self::scores($changeFightStatusCommand->getScores()) : [];

        if (!$this->currentUserProvider->isGranted('ROLE_ADMIN')) {
            throw new PermissionDeniedException('only an administrator settles a dispute');
        }

        $fight = $this->fightRepository->findOneBy(['id' => $fightId->getValue()]);
        if (!$fight instanceof Fight) {
            throw new NotFoundException('fight not found');
        }

        $one = $fight->getCompetitorOne()->getValue();
        $two = $fight->getCompetitorTwo()->getValue();
        $resultOne = $this->resultRepository->findOneBy(['fight' => $fightId->getValue(), 'competitor' => $one]);
        $resultTwo = $this->resultRepository->findOneBy(['fight' => $fightId->getValue(), 'competitor' => $two]);
        if (!$resultOne instanceof Result || !$resultTwo instanceof Result) {
            throw new NotFoundException('result not found');
        }

        if ('finished' === $status) {
            if (!isset($scores[$one], $scores[$two])) {
                throw new ValidationException('scores must give the score of both sides of the fight');
            }
            Fight::arbitrate($fight, $resultOne, $scores[$one], $resultTwo, $scores[$two]);
        } else {
            Fight::reopen($fight, $resultOne, $resultTwo);
        }

        $this->resultRepository->save($resultOne);
        $this->resultRepository->save($resultTwo);
        $this->fightRepository->save($fight);

        foreach ($fight->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return json_encode(
            $this->normalizeFight($fight, [$resultOne, $resultTwo], $this->competitorRegistryProvider->describe([$one, $two])),
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param (array<string, mixed> | null) $given
     *
     * @return array<string, Score>
     */
    private static function scores(?array $given): array
    {
        if (null === $given || 2 !== \count($given)) {
            throw new ValidationException('scores must give the score of both sides of the fight');
        }

        $scores = [];
        foreach ($given as $competitor => $score) {
            if (!\is_int($score)) {
                throw new ValidationException('a score is a whole number, zero or more');
            }
            $scores[(string) $competitor] = new Score($score);
        }

        return $scores;
    }

    /**
     * @param list<Result> $results
     * @param array<string, array{type: string, reference: string, name: ?string, tag: ?string}> $described
     *
     * @return array<string, mixed>
     */
    private function normalizeFight(Fight $fight, array $results, array $described): array
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
            'mySide' => null,
            'sides' => $sides,
            'createdAt' => $fight->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $fight->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
