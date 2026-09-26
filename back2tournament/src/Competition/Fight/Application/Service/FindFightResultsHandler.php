<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Model\FindFightResultsQuery;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorIdProviderInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindFightResultsHandler
{
    private CurrentUserProviderInterface $currentUserProvider;
    private NormalizerInterface $serializer;
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private GameRepositoryInterface $gameRepository;
    private CompetitorIdProviderInterface $competitorIdProvider;

    public function __construct(
        NormalizerInterface $serializer,
        CurrentUserProviderInterface $currentUserProvider,
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        GameRepositoryInterface $gameRepository,
        CompetitorIdProviderInterface $competitorIdProvider
    ){
        $this->serializer = $serializer;
        $this->currentUserProvider = $currentUserProvider;
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->gameRepository = $gameRepository;
        $this->competitorIdProvider = $competitorIdProvider;
    }

    public function __invoke(FindFightResultsQuery $findFightResultsQuery): string
    {
        $user = $this->currentUserProvider->getUser();

        $gameId = $findFightResultsQuery->getGameId();
        $game = $this->gameRepository->findOneBy(['game' => new GameId($gameId)->getValue()]);
        if (!$game) {
            throw new NotFoundException('Game not found');
        }

        $fightId = $findFightResultsQuery->getFightId();
        $fight = $this->fightRepository->findOneBy(['fight' => new FightId($fightId)->getValue()]);
        if (!$fight) {
            throw new NotFoundException('Fight not found');
        }

        $competitorId = $this->competitorIdProvider->byUserAndGame($user->getId(), $gameId);
        if (!$competitorId) {
            throw new NotFoundException('Competitor not found');
        }
        $result = $this->resultRepository->findOneBy(['fight' => $fightId, 'competitor' => $competitorId]);
        if (!$result) {
            throw new NotFoundException('Result not found');
        }

        $normalized = $this->serializer->normalize($result);

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
