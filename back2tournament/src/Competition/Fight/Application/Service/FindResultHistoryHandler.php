<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Competition\Fight\Application\Model\FindResultHistoryQuery;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class FindResultHistoryHandler
{
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;

    public function __construct(
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
    ) {
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
    }

    public function __invoke(FindResultHistoryQuery $findResultHistoryQuery): string
    {
        $page = $findResultHistoryQuery->getPage();
        $limit = $findResultHistoryQuery->getLimit();

        // The player profile itself and the teams it plays in, or the clan's teams.
        $playerId = $findResultHistoryQuery->getPlayerId();
        $competitors = null !== $playerId
            ? $this->competitorRegistryProvider->competitorsOfPlayer(new PlayerId($playerId)->getValue())
            : $this->competitorRegistryProvider->competitorsOfClan(new ClanId((string) $findResultHistoryQuery->getClanId())->getValue());

        if ([] === $competitors) {
            return $this->page([], 0, $page, $limit);
        }

        // A result is settled once confirmed: its status is then the outcome.
        $criteria = [
            'competitor' => $competitors,
            'status' => [ResultStatus::WIN, ResultStatus::LOSS, ResultStatus::DRAW],
        ];

        $results = $this->resultRepository->findBy(
            $criteria,
            ['updatedAt' => 'DESC', 'id' => 'ASC'],
            $limit,
            $findResultHistoryQuery->getOffset(),
        );

        $fights = [];
        $scores = [];
        if ([] !== $results) {
            $fightIds = array_values(array_unique(array_map(static fn (Result $result): string => $result->getFight()->getValue(), $results)));
            foreach ($this->fightRepository->findBy(['id' => $fightIds]) as $fight) {
                $fights[$fight->getId()->getValue()] = $fight;
            }
            // Both sides' results, so that each item tells what the other side scored.
            foreach ($this->resultRepository->findBy(['fight' => $fightIds]) as $sideResult) {
                $scores[$sideResult->getFight()->getValue()][$sideResult->getCompetitor()->getValue()] = $sideResult->getScore();
            }
        }

        $competitorIds = [];
        foreach ($fights as $fight) {
            $competitorIds[] = $fight->getCompetitorOne()->getValue();
            $competitorIds[] = $fight->getCompetitorTwo()->getValue();
        }
        $described = $this->competitorRegistryProvider->describe($competitorIds);

        $items = [];
        foreach ($results as $result) {
            $fight = $fights[$result->getFight()->getValue()] ?? null;
            if (!$fight instanceof Fight) {
                continue;
            }

            $items[] = $this->normalizeResult($result, $fight, $scores[$fight->getId()->getValue()] ?? [], $described);
        }

        return $this->page($items, $this->resultRepository->count($criteria), $page, $limit);
    }

    /**
     * A settled fight, told from the side of the result.
     *
     * @param array<string, int>                                                   $scores    both sides' scores, keyed by competitor id
     * @param array<string, array{type: string, reference: string, name: ?string}> $described the sides, keyed by competitor id
     *
     * @return array<string, mixed>
     */
    private function normalizeResult(Result $result, Fight $fight, array $scores, array $described): array
    {
        $side = $result->getCompetitor()->getValue();
        $opponent = $fight->opponentOf($result->getCompetitor())?->getValue();

        return [
            'fight' => ['value' => $fight->getId()->getValue()],
            'game' => ['value' => $fight->getGame()->getValue()],
            'teamSize' => $fight->getTeamSize(),
            'tournament' => null === $fight->getTournament() ? null : ['value' => $fight->getTournament()->getValue()],
            'outcome' => $result->getStatus()->value,
            'settledAt' => $result->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'side' => $this->normalizeSide($side, $scores[$side] ?? $result->getScore(), $described),
            'opponent' => null === $opponent ? null : $this->normalizeSide($opponent, $scores[$opponent] ?? 0, $described),
        ];
    }

    /**
     * @param array<string, array{type: string, reference: string, name: ?string}> $described
     *
     * @return array<string, mixed>
     */
    private function normalizeSide(string $competitorId, int $score, array $described): array
    {
        $description = $described[$competitorId] ?? null;

        return [
            'competitor' => ['value' => $competitorId],
            'type' => $description['type'] ?? null,
            'reference' => null === $description ? null : ['value' => $description['reference']],
            'name' => $description['name'] ?? null,
            'score' => $score,
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function page(array $items, int $total, int $page, int $limit): string
    {
        return json_encode([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
        ], JSON_THROW_ON_ERROR);
    }
}
