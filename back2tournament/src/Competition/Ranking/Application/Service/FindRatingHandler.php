<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Service;

use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Ranking\Application\Model\FindRatingQuery;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use App\Competition\Shared\Domain\Provider\ClanTagProviderInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class FindRatingHandler
{
    private RatingRepositoryInterface $ratingRepository;
    private GameRepositoryInterface $gameRepository;
    private PlayerRepositoryInterface $playerRepository;
    private ClanRepositoryInterface $clanRepository;
    private ClanTagProviderInterface $clanTagProvider;

    public function __construct(
        RatingRepositoryInterface $ratingRepository,
        GameRepositoryInterface $gameRepository,
        PlayerRepositoryInterface $playerRepository,
        ClanRepositoryInterface $clanRepository,
        ClanTagProviderInterface $clanTagProvider,
    ) {
        $this->ratingRepository = $ratingRepository;
        $this->gameRepository = $gameRepository;
        $this->playerRepository = $playerRepository;
        $this->clanRepository = $clanRepository;
        $this->clanTagProvider = $clanTagProvider;
    }

    public function __invoke(FindRatingQuery $findRatingQuery): string
    {
        $subjectType = $findRatingQuery->getSubjectType();
        $subject = RankingSubject::PLAYER === $subjectType
            ? $this->player($findRatingQuery->getSubjectId())
            : $this->clan($findRatingQuery->getSubjectId());

        $game = $this->gameRepository->findOneBy(['id' => $subject['game']]);
        $formats = $game instanceof Game ? $game->getTeamSizes() : [];

        $ratings = [];
        foreach ($this->ratingRepository->findBy(['subjectType' => $subjectType, 'subject' => $subject['id']]) as $rating) {
            $ratings[$rating->getTeamSize()] = $rating;
        }

        $perFormat = [];
        foreach ($formats as $teamSize) {
            $rating = $ratings[$teamSize] ?? null;
            $rated = $rating instanceof Rating;

            $perFormat[] = [
                'teamSize' => $teamSize,
                'rank' => $rated && $subject['ranked'] ? 1 + $this->ratingRepository->countAbove($subjectType, $subject['game'], $teamSize, $rating->getValue()) : null,
                'total' => $this->ratingRepository->countRanking($subjectType, $subject['game'], $teamSize),
                'rating' => $rated ? $rating->getValue() : Rating::INITIAL,
                'fights' => $rated ? $rating->getFights() : 0,
                'wins' => $rated ? $rating->getWins() : 0,
                'draws' => $rated ? $rating->getDraws() : 0,
                'losses' => $rated ? $rating->getLosses() : 0,
            ];
        }

        return json_encode([
            'subject' => [
                'type' => $subjectType->value,
                'id' => ['value' => $subject['id']],
                'name' => $subject['name'],
                'tag' => $subject['tag'],
            ],
            'game' => ['value' => $subject['game']],
            'ratings' => $perFormat,
        ], JSON_THROW_ON_ERROR);
    }

    /** @return array{id: string, game: string, name: string, tag: ?string, ranked: bool} */
    private function player(string $playerId): array
    {
        $player = $this->playerRepository->findOneBy(['id' => new PlayerId($playerId)->getValue()]);
        if (!$player instanceof Player) {
            throw new NotFoundException('player profile not found');
        }

        $id = $player->getId()->getValue();

        return [
            'id' => $id,
            'game' => $player->getGame()->getValue(),
            'name' => (string) $player->getBattletag(),
            'tag' => $this->clanTagProvider->clansOfPlayers([$id])[$id]['tag'] ?? null,
            'ranked' => null === $player->getAnonymizedAt(),
        ];
    }

    /** @return array{id: string, game: string, name: string, tag: string, ranked: bool} */
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
            'ranked' => !$clan->isDissolved(),
        ];
    }
}
