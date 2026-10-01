<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Tournament\Domain\Enum\TournamentStatus;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\ConflictException;

final class AccountErasureProvider implements AccountErasureProviderInterface
{
    public const LEADS_A_CLAN = 'you lead a clan: dissolve it first';
    public const ORGANIZES_A_TOURNAMENT = 'you organize a tournament still open for registration: start or cancel it first';

    private PlayerRepositoryInterface $playerRepository;
    private ClanRepositoryInterface $clanRepository;
    private TournamentRepositoryInterface $tournamentRepository;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        ClanRepositoryInterface $clanRepository,
        TournamentRepositoryInterface $tournamentRepository,
    ) {
        $this->playerRepository = $playerRepository;
        $this->clanRepository = $clanRepository;
        $this->tournamentRepository = $tournamentRepository;
    }

    public function ensureErasable(string $userId): void
    {
        $profileIds = array_map(
            static fn (Player $player): string => $player->getId()->getValue(),
            $this->playerRepository->findBy(['user' => $userId]),
        );

        if ([] !== $profileIds && [] !== $this->clanRepository->findBy(['leader' => $profileIds, 'dissolvedAt' => null])) {
            throw new ConflictException(self::LEADS_A_CLAN);
        }

        if (null !== $this->tournamentRepository->findOneBy(['organizer' => $userId, 'status' => TournamentStatus::UPCOMING])) {
            throw new ConflictException(self::ORGANIZES_A_TOURNAMENT);
        }
    }
}
