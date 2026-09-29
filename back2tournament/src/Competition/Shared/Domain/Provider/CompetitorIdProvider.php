<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Competitor\Domain\Repository\CompetitorRepositoryInterface;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\NotFoundException;

final class CompetitorIdProvider implements CompetitorIdProviderInterface
{
    private CompetitorRepositoryInterface $competitorRepository;
    private PlayerRepositoryInterface $playerRepository;
    private FightRepositoryInterface $fightRepository;

    public function __construct(
        CompetitorRepositoryInterface $competitorRepository,
        PlayerRepositoryInterface $playerRepository,
        FightRepositoryInterface $fightRepository,
    ) {
        $this->competitorRepository = $competitorRepository;
        $this->playerRepository = $playerRepository;
        $this->fightRepository = $fightRepository;
    }

    public function byUserAndGame(string $userId, string $gameId): string
    {
        /** @var (Player | null) $player */
        $player = $this->playerRepository->findOneBy(['user' => $userId, 'game' => $gameId]);
        if (!$player) {
            throw new NotFoundException(\sprintf('user %s has no player profile in game %s', $userId, $gameId));
        }

        /** @var (Competitor | null) $competitor */
        $competitor = $this->competitorRepository->findOneBy([
            'type' => CompetitorType::PLAYER,
            'reference' => $player->getId()->getValue(),
        ]);
        if (!$competitor) {
            throw new NotFoundException(\sprintf('player %s does not compete yet', $player->getId()->getValue()));
        }

        return $competitor->getId()->getValue();
    }

    public function takesPartInFights(string $playerId): bool
    {
        /** @var (Competitor | null) $competitor */
        $competitor = $this->competitorRepository->findOneBy([
            'type' => CompetitorType::PLAYER,
            'reference' => $playerId,
        ]);
        if (!$competitor) {
            return false;
        }

        $competitorId = $competitor->getId()->getValue();

        return null !== $this->fightRepository->findOneBy(['competitorOne' => $competitorId])
            || null !== $this->fightRepository->findOneBy(['competitorTwo' => $competitorId]);
    }
}
