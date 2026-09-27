<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Service;

use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Ranking\Application\Model\FindRankingQuery;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class FindRankingHandler
{
    private RatingRepositoryInterface $ratingRepository;
    private GameRepositoryInterface $gameRepository;
    private PlayerRepositoryInterface $playerRepository;
    private ClanRepositoryInterface $clanRepository;

    public function __construct(
        RatingRepositoryInterface $ratingRepository,
        GameRepositoryInterface $gameRepository,
        PlayerRepositoryInterface $playerRepository,
        ClanRepositoryInterface $clanRepository,
    ) {
        $this->ratingRepository = $ratingRepository;
        $this->gameRepository = $gameRepository;
        $this->playerRepository = $playerRepository;
        $this->clanRepository = $clanRepository;
    }

    public function __invoke(FindRankingQuery $findRankingQuery): string
    {
        $gameId = new GameId($findRankingQuery->getGameId());
        if (!$this->gameRepository->findOneBy(['id' => $gameId->getValue()]) instanceof Game) {
            throw new NotFoundException('game not found');
        }

        $subjectType = $findRankingQuery->getSubjectType();
        $offset = $findRankingQuery->getOffset();

        $ratings = $this->ratingRepository->findRanking($subjectType, $gameId->getValue(), $findRankingQuery->getLimit(), $offset);
        $subjects = $this->subjects($subjectType, array_map(static fn (Rating $rating): string => $rating->getSubject(), $ratings));

        // Equal ratings share a rank; the next one down takes its place in the list.
        $items = [];
        $rank = 0;
        $previous = null;
        foreach ($ratings as $index => $rating) {
            if (null === $previous) {
                $rank = 1 + $this->ratingRepository->countAbove($subjectType, $gameId->getValue(), $rating->getValue());
            } elseif ($rating->getValue() < $previous) {
                $rank = $offset + $index + 1;
            }
            $previous = $rating->getValue();

            $items[] = $this->normalizeRating($rating, $rank, $subjects[$rating->getSubject()] ?? null);
        }

        $total = $this->ratingRepository->countRanking($subjectType, $gameId->getValue());
        $limit = $findRankingQuery->getLimit();

        return json_encode([
            'items' => $items,
            'total' => $total,
            'page' => $findRankingQuery->getPage(),
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * The names of the ranked player profiles or clans, keyed by id.
     *
     * @param list<string> $subjectIds
     *
     * @return array<string, array{name: string, tag: ?string}>
     */
    private function subjects(RankingSubject $subjectType, array $subjectIds): array
    {
        if ([] === $subjectIds) {
            return [];
        }

        $subjects = [];
        if (RankingSubject::PLAYER === $subjectType) {
            foreach ($this->playerRepository->findBy(['id' => $subjectIds]) as $player) {
                $subjects[$player->getId()->getValue()] = ['name' => (string) $player->getBattletag(), 'tag' => null];
            }

            return $subjects;
        }

        foreach ($this->clanRepository->findBy(['id' => $subjectIds]) as $clan) {
            $subjects[$clan->getId()->getValue()] = ['name' => $clan->getName(), 'tag' => $clan->getTag()];
        }

        return $subjects;
    }

    /**
     * @param array{name: string, tag: ?string}|null $subject
     *
     * @return array<string, mixed>
     */
    private function normalizeRating(Rating $rating, int $rank, ?array $subject): array
    {
        return [
            'rank' => $rank,
            'rating' => $rating->getValue(),
            'fights' => $rating->getFights(),
            'wins' => $rating->getWins(),
            'draws' => $rating->getDraws(),
            'losses' => $rating->getLosses(),
            'subject' => [
                'type' => $rating->getSubjectType()->value,
                'id' => ['value' => $rating->getSubject()],
                'name' => $subject['name'] ?? null,
                'tag' => $subject['tag'] ?? null,
            ],
        ];
    }
}
