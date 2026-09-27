<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Model\FindFightsQuery;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class FindFightsHandler
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

    public function __invoke(FindFightsQuery $findFightsQuery): string
    {
        $statuses = match ($findFightsQuery->getStatus()) {
            'all' => null,
            'pending' => [ResultStatus::PENDING],
            'reporting' => [ResultStatus::REPORTING],
            'finished' => [ResultStatus::WIN, ResultStatus::LOSS, ResultStatus::DRAW],
            default => throw new ValidationException('status must be pending, reporting, finished or all'),
        };
        $gameId = null === $findFightsQuery->getGameId() ? null : new GameId($findFightsQuery->getGameId())->getValue();

        if (!$this->currentUserProvider->isGranted('ROLE_ADMIN')) {
            throw new PermissionDeniedException('only an administrator lists every fight');
        }

        // A fight id pasted from a conversation finds that fight; a name, the
        // fights of the player profiles and teams that bear it.
        $competitors = null;
        $fightId = null;
        $search = $findFightsQuery->getSearch();
        if (null !== $search) {
            $fightId = Uuid::isValid($search) ? strtolower($search) : null;
            $competitors = $this->competitorRegistryProvider->named($search, $gameId);
            if (null === $fightId && [] === $competitors) {
                return $this->page([], 0, $findFightsQuery);
            }
        }

        $fights = $this->fightRepository->findPage($statuses, $gameId, $competitors, $fightId, $findFightsQuery->getLimit(), $findFightsQuery->getOffset());
        $total = $this->fightRepository->countPage($statuses, $gameId, $competitors, $fightId);

        $results = [];
        $competitorIds = [];
        if ([] !== $fights) {
            $fightIds = array_map(static fn (Fight $fight): string => $fight->getId()->getValue(), $fights);
            foreach ($this->resultRepository->findBy(['fight' => $fightIds]) as $result) {
                $results[$result->getFight()->getValue()][] = $result;
            }
            foreach ($fights as $fight) {
                $competitorIds[] = $fight->getCompetitorOne()->getValue();
                $competitorIds[] = $fight->getCompetitorTwo()->getValue();
            }
        }
        $described = $this->competitorRegistryProvider->describe($competitorIds);

        $items = [];
        foreach ($fights as $fight) {
            $items[] = $this->normalizeFight($fight, $results[$fight->getId()->getValue()] ?? [], $described);
        }

        return $this->page($items, $total, $findFightsQuery);
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function page(array $items, int $total, FindFightsQuery $findFightsQuery): string
    {
        return json_encode([
            'items' => $items,
            'total' => $total,
            'page' => $findFightsQuery->getPage(),
            'limit' => $findFightsQuery->getLimit(),
            'pages' => (int) ceil($total / $findFightsQuery->getLimit()),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * The fight as a whole, and its two sides, each named and carrying its
     * own result.
     *
     * @param list<Result>                                                         $results
     * @param array<string, array{type: string, reference: string, name: ?string, tag: ?string}> $described the sides, keyed by competitor id
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
