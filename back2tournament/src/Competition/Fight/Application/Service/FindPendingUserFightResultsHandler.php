<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Competitor\Domain\Repository\CompetitorRepositoryInterface;
use App\Competition\Fight\Application\Model\FindPendingUserFightResultsQuery;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindPendingUserFightResultsHandler
{
    private CurrentUserProviderInterface $currentUserProvider;
    private PlayerRepositoryInterface $playerRepository;
    private CompetitorRepositoryInterface $competitorRepository;
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private NormalizerInterface $serializer;

    public function __construct(
        CurrentUserProviderInterface $currentUserProvider,
        PlayerRepositoryInterface $playerRepository,
        CompetitorRepositoryInterface $competitorRepository,
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        NormalizerInterface $serializer,
    ) {
        $this->currentUserProvider = $currentUserProvider;
        $this->playerRepository = $playerRepository;
        $this->competitorRepository = $competitorRepository;
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->serializer = $serializer;
    }

    public function __invoke(FindPendingUserFightResultsQuery $findPendingUserFightResultsQuery): string
    {
        $page = $findPendingUserFightResultsQuery->getPage();
        $limit = $findPendingUserFightResultsQuery->getLimit();

        $playersByCompetitor = $this->playersByCompetitor($this->currentUserProvider->getUser()->getId());

        if ([] === $playersByCompetitor) {
            return $this->page([], 0, $page, $limit);
        }

        $criteria = [
            'competitor' => array_keys($playersByCompetitor),
            'status' => [ResultStatus::PENDING, ResultStatus::REPORTING],
        ];

        $results = $this->resultRepository->findBy(
            $criteria,
            ['createdAt' => 'ASC'],
            $limit,
            $findPendingUserFightResultsQuery->getOffset(),
        );

        $opponents = $this->opponentsByResult($results);

        $items = [];
        foreach ($results as $result) {
            /** @var array<string, mixed> $item */
            $item = $this->serializer->normalize($result);

            $player = $playersByCompetitor[$result->getCompetitor()->getValue()] ?? null;
            $item['game'] = null === $player ? null : ['value' => $player->getGame()->getValue()];
            $item['player'] = null === $player ? null : $this->profile($player);
            $item['opponent'] = $opponents[$result->getId()->getValue()] ?? null;

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
     * @return array<string, Player> the caller's players, keyed by the id of the competitor each plays as
     */
    private function playersByCompetitor(string $userId): array
    {
        $players = $this->playerRepository->findBy(['user' => $userId]);
        if ([] === $players) {
            return [];
        }

        $playersById = [];
        foreach ($players as $player) {
            $playersById[$player->getId()->getValue()] = $player;
        }

        /** @var list<Competitor> $competitors */
        $competitors = $this->competitorRepository->findBy([
            'type' => CompetitorType::PLAYER,
            'reference' => array_keys($playersById),
        ]);

        $byCompetitor = [];
        foreach ($competitors as $competitor) {
            $byCompetitor[$competitor->getId()->getValue()] = $playersById[$competitor->getReference()];
        }

        return $byCompetitor;
    }

    /**
     * @param list<Result> $results
     *
     * @return array<string, array<string, mixed>> the other side of each result's fight, keyed by result id
     */
    private function opponentsByResult(array $results): array
    {
        if ([] === $results) {
            return [];
        }

        $fightIds = [];
        foreach ($results as $result) {
            $fightIds[$result->getFight()->getValue()] = true;
        }

        /** @var list<Fight> $fights */
        $fights = $this->fightRepository->findBy(['id' => array_keys($fightIds)]);

        $fightsById = [];
        foreach ($fights as $fight) {
            $fightsById[$fight->getId()->getValue()] = $fight;
        }

        $opponentByResult = [];
        foreach ($results as $result) {
            $fight = $fightsById[$result->getFight()->getValue()] ?? null;
            if (null === $fight) {
                continue;
            }

            $one = $fight->getCompetitorOne()->getValue();
            $two = $fight->getCompetitorTwo()->getValue();
            $opponentByResult[$result->getId()->getValue()] = $result->getCompetitor()->getValue() === $one ? $two : $one;
        }

        return $this->describe($opponentByResult);
    }

    /**
     * @param array<string, string> $opponentByResult the opponent's competitor id, keyed by result id
     *
     * @return array<string, array<string, mixed>>
     */
    private function describe(array $opponentByResult): array
    {
        if ([] === $opponentByResult) {
            return [];
        }

        /** @var list<Competitor> $competitors */
        $competitors = $this->competitorRepository->findBy(['id' => array_values(array_unique($opponentByResult))]);

        $referenceByCompetitor = [];
        $playerReferences = [];
        foreach ($competitors as $competitor) {
            $referenceByCompetitor[$competitor->getId()->getValue()] = $competitor;

            if (CompetitorType::PLAYER === $competitor->getType()) {
                $playerReferences[] = $competitor->getReference();
            }
        }

        $playersById = [];
        if ([] !== $playerReferences) {
            foreach ($this->playerRepository->findBy(['id' => $playerReferences]) as $player) {
                $playersById[$player->getId()->getValue()] = $player;
            }
        }

        $described = [];
        foreach ($opponentByResult as $resultId => $competitorId) {
            $competitor = $referenceByCompetitor[$competitorId] ?? null;
            $player = null === $competitor ? null : ($playersById[$competitor->getReference()] ?? null);

            $described[$resultId] = [
                'competitor' => ['value' => $competitorId],
                'player' => null === $player ? null : $this->profile($player),
            ];
        }

        return $described;
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(Player $player): array
    {
        return [
            'id' => ['value' => $player->getId()->getValue()],
            'battletag' => $player->getBattletag(),
        ];
    }
}
