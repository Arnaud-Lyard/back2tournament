<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Fight\Application\Model\FindPendingUserFightResultsQuery;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindPendingUserFightResultsHandler
{
    private CurrentUserProviderInterface $currentUserProvider;
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private NormalizerInterface $serializer;

    public function __construct(
        CurrentUserProviderInterface $currentUserProvider,
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        NormalizerInterface $serializer,
    ) {
        $this->currentUserProvider = $currentUserProvider;
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->serializer = $serializer;
    }

    public function __invoke(FindPendingUserFightResultsQuery $findPendingUserFightResultsQuery): string
    {
        $page = $findPendingUserFightResultsQuery->getPage();
        $limit = $findPendingUserFightResultsQuery->getLimit();

        // The caller's own player profiles, and the teams one of them leads.
        $represented = $this->competitorRegistryProvider->representedBy((string) $this->currentUserProvider->getUser()->getId());

        if ([] === $represented) {
            return $this->page([], 0, $page, $limit);
        }

        $criteria = [
            'competitor' => $represented,
            'status' => [ResultStatus::PENDING, ResultStatus::REPORTING],
        ];

        $results = $this->resultRepository->findBy(
            $criteria,
            ['createdAt' => 'ASC'],
            $limit,
            $findPendingUserFightResultsQuery->getOffset(),
        );

        $fights = [];
        $scores = [];
        if ([] !== $results) {
            $fightIds = array_values(array_unique(array_map(static fn (Result $result): string => $result->getFight()->getValue(), $results)));
            foreach ($this->fightRepository->findBy(['id' => $fightIds]) as $fight) {
                $fights[$fight->getId()->getValue()] = $fight;
            }
            // Both sides' results, so that each item can tell what the other side scored.
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
            /** @var array<string, mixed> $item */
            $item = $this->serializer->normalize($result);

            $fight = $fights[$result->getFight()->getValue()] ?? null;
            $mine = $result->getCompetitor();
            $opponent = $fight?->opponentOf($mine);

            $item['game'] = null === $fight ? null : ['value' => $fight->getGame()->getValue()];
            $item['teamSize'] = $fight?->getTeamSize() ?? 1;
            $item['tournament'] = null === $fight?->getTournament() ? null : ['value' => $fight->getTournament()->getValue()];
            $item['declaredBy'] = null === $fight?->getDeclaredBy() ? null : ['value' => $fight->getDeclaredBy()->getValue()];
            $item['side'] = self::side($mine->getValue(), $described);
            $item['player'] = self::profile($described[$mine->getValue()] ?? null);
            $item['opponent'] = null === $opponent ? null : self::side($opponent->getValue(), $described) + [
                'player' => self::profile($described[$opponent->getValue()] ?? null),
                'score' => $scores[$result->getFight()->getValue()][$opponent->getValue()] ?? 0,
            ];

            $items[] = $item;
        }

        return $this->page($items, $this->resultRepository->count($criteria), $page, $limit);
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

    /**
     * @param array<string, array{type: string, reference: string, name: ?string, tag: ?string}> $described
     *
     * @return array<string, mixed>
     */
    private static function side(string $competitorId, array $described): array
    {
        return [
            'competitor' => ['value' => $competitorId],
            'type' => $described[$competitorId]['type'] ?? null,
            'name' => $described[$competitorId]['name'] ?? null,
            'tag' => $described[$competitorId]['tag'] ?? null,
        ];
    }

    /**
     * The player profile behind a 1v1 side; null for a team.
     *
     * @param array{type: string, reference: string, name: ?string, tag: ?string}|null $description
     *
     * @return array<string, mixed>|null
     */
    private static function profile(?array $description): ?array
    {
        if (null === $description || CompetitorType::PLAYER->value !== $description['type']) {
            return null;
        }

        return [
            'id' => ['value' => $description['reference']],
            'battletag' => $description['name'],
        ];
    }
}
