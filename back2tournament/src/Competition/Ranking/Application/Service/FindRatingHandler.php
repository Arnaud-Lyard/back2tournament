<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Service;

use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Ranking\Application\Model\FindRatingQuery;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class FindRatingHandler
{
    private RatingRepositoryInterface $ratingRepository;
    private PlayerRepositoryInterface $playerRepository;
    private ClanRepositoryInterface $clanRepository;

    public function __construct(
        RatingRepositoryInterface $ratingRepository,
        PlayerRepositoryInterface $playerRepository,
        ClanRepositoryInterface $clanRepository,
    ) {
        $this->ratingRepository = $ratingRepository;
        $this->playerRepository = $playerRepository;
        $this->clanRepository = $clanRepository;
    }

    public function __invoke(FindRatingQuery $findRatingQuery): string
    {
        $subjectType = $findRatingQuery->getSubjectType();
        $subject = RankingSubject::PLAYER === $subjectType
            ? $this->player($findRatingQuery->getSubjectId())
            : $this->clan($findRatingQuery->getSubjectId());

        // A player profile or a clan with no settled fight yet stands at the
        // initial rating, unranked.
        $rating = $this->ratingRepository->findOneBy(['subjectType' => $subjectType, 'subject' => $subject['id']]);
        $rated = $rating instanceof Rating;

        return json_encode([
            'subject' => [
                'type' => $subjectType->value,
                'id' => ['value' => $subject['id']],
                'name' => $subject['name'],
                'tag' => $subject['tag'],
            ],
            'game' => ['value' => $subject['game']],
            'rank' => $rated ? 1 + $this->ratingRepository->countAbove($subjectType, $subject['game'], $rating->getValue()) : null,
            'total' => $this->ratingRepository->countRanking($subjectType, $subject['game']),
            'rating' => $rated ? $rating->getValue() : Rating::INITIAL,
            'fights' => $rated ? $rating->getFights() : 0,
            'wins' => $rated ? $rating->getWins() : 0,
            'draws' => $rated ? $rating->getDraws() : 0,
            'losses' => $rated ? $rating->getLosses() : 0,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{id: string, game: string, name: string, tag: null}
     */
    private function player(string $playerId): array
    {
        $player = $this->playerRepository->findOneBy(['id' => new PlayerId($playerId)->getValue()]);
        if (!$player instanceof Player) {
            throw new NotFoundException('player profile not found');
        }

        return [
            'id' => $player->getId()->getValue(),
            'game' => $player->getGame()->getValue(),
            'name' => (string) $player->getBattletag(),
            'tag' => null,
        ];
    }

    /**
     * @return array{id: string, game: string, name: string, tag: string}
     */
    private function clan(string $clanId): array
    {
        $clan = $this->clanRepository->findOneBy(['id' => new ClanId($clanId)->getValue()]);
        if (!$clan instanceof Clan) {
            throw new NotFoundException('clan not found');
        }

        return [
            'id' => $clan->getId()->getValue(),
            'game' => $clan->getGame()->getValue(),
            'name' => $clan->getName(),
            'tag' => $clan->getTag(),
        ];
    }
}
