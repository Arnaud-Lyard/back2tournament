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
use App\Competition\Shared\Domain\Provider\ClanTagProviderInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class FindResultHistoryHandler
{
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private ClanTagProviderInterface $clanTagProvider;

    public function __construct(
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        ClanTagProviderInterface $clanTagProvider,
    ) {
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->clanTagProvider = $clanTagProvider;
    }

    public function __invoke(FindResultHistoryQuery $findResultHistoryQuery): string
    {
        $page = $findResultHistoryQuery->getPage();
        $limit = $findResultHistoryQuery->getLimit();
        $offset = $findResultHistoryQuery->getOffset();

        $playerId = $findResultHistoryQuery->getPlayerId();
        if (null !== $playerId) {
            $competitors = $this->competitorRegistryProvider->competitorsOfPlayer(new PlayerId($playerId)->getValue());
            if ([] === $competitors) {
                return $this->page([], 0, $page, $limit);
            }

            $criteria = [
                'competitor' => $competitors,
                'status' => [ResultStatus::WIN, ResultStatus::LOSS, ResultStatus::DRAW],
            ];

            $results = $this->resultRepository->findBy($criteria, ['updatedAt' => 'DESC', 'id' => 'ASC'], $limit, $offset);
            $total = $this->resultRepository->count($criteria);
        } else {
            $clanId = new ClanId((string) $findResultHistoryQuery->getClanId())->getValue();

            $results = $this->resultRepository->findSettledAgainstOtherClans($clanId, $limit, $offset);
            $total = $this->resultRepository->countSettledAgainstOtherClans($clanId);
        }

        $fights = [];
        $scores = [];
        $clans = [];
        $clanIds = [];
        if ([] !== $results) {
            $fightIds = array_values(array_unique(array_map(static fn (Result $result): string => $result->getFight()->getValue(), $results)));
            foreach ($this->fightRepository->findBy(['id' => $fightIds]) as $fight) {
                $fights[$fight->getId()->getValue()] = $fight;
            }
            foreach ($this->resultRepository->findBy(['fight' => $fightIds]) as $sideResult) {
                $fightId = $sideResult->getFight()->getValue();
                $competitorId = $sideResult->getCompetitor()->getValue();
                $clan = $sideResult->getClan()?->getValue();

                $scores[$fightId][$competitorId] = $sideResult->getScore();
                $clans[$fightId][$competitorId] = $clan;
                if (null !== $clan) {
                    $clanIds[$clan] = true;
                }
            }
        }
        $tags = $this->clanTagProvider->tagsOfClans(array_keys($clanIds));
        $dissolved = array_fill_keys($this->clanTagProvider->dissolvedAmong(array_keys($clanIds)), true);

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

            $fightId = $fight->getId()->getValue();
            $items[] = $this->normalizeResult($result, $fight, $scores[$fightId] ?? [], $described, $clans[$fightId] ?? [], $tags, $dissolved);
        }

        return $this->page($items, $total, $page, $limit);
    }

    /**
     * @param array<string, int> $scores
     * @param array<string, array{type: string, reference: string, name: ?string, tag: ?string}> $described
     * @param array<string, ?string> $clans
     * @param array<string, string> $tags
     * @param array<string, true> $dissolved
     *
     * @return array<string, mixed>
     */
    private function normalizeResult(Result $result, Fight $fight, array $scores, array $described, array $clans, array $tags, array $dissolved): array
    {
        $side = $result->getCompetitor()->getValue();
        $opponent = $fight->opponentOf($result->getCompetitor())?->getValue();
        $tagOf = static function (string $competitorId) use ($clans, $tags): ?string {
            $clan = $clans[$competitorId] ?? null;

            return null === $clan ? null : ($tags[$clan] ?? null);
        };
        $dissolvedOf = static fn (string $competitorId): bool => isset($dissolved[$clans[$competitorId] ?? '']);

        return [
            'fight' => ['value' => $fight->getId()->getValue()],
            'game' => ['value' => $fight->getGame()->getValue()],
            'teamSize' => $fight->getTeamSize(),
            'tournament' => null === $fight->getTournament() ? null : ['value' => $fight->getTournament()->getValue()],
            'outcome' => $result->getStatus()->value,
            'arbitrated' => $fight->isArbitrated(),
            'settledAt' => $result->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'side' => $this->normalizeSide($side, $scores[$side] ?? $result->getScore(), $described, $tagOf($side), $dissolvedOf($side)),
            'opponent' => null === $opponent ? null : $this->normalizeSide($opponent, $scores[$opponent] ?? 0, $described, $tagOf($opponent), $dissolvedOf($opponent)),
        ];
    }

    /**
     * @param array<string, array{type: string, reference: string, name: ?string, tag: ?string}> $described
     *
     * @return array<string, mixed>
     */
    private function normalizeSide(string $competitorId, int $score, array $described, ?string $tag, bool $clanDissolved): array
    {
        $description = $described[$competitorId] ?? null;

        return [
            'competitor' => ['value' => $competitorId],
            'type' => $description['type'] ?? null,
            'reference' => null === $description ? null : ['value' => $description['reference']],
            'name' => $description['name'] ?? null,
            'tag' => $tag,
            'clanDissolved' => $clanDissolved,
            'score' => $score,
        ];
    }

    /** @param list<array<string, mixed>> $items */
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
