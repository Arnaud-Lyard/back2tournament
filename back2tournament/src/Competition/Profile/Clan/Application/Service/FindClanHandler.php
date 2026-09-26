<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Competition\Profile\Clan\Application\Model\FindClanQuery;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Enum\ClanRole;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Application\Service\TeamView;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindClanHandler
{
    private ClanRepositoryInterface $clanRepository;
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private PlayerRepositoryInterface $playerRepository;
    private TeamRepositoryInterface $teamRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private NormalizerInterface $serializer;

    public function __construct(
        ClanRepositoryInterface $clanRepository,
        ClanMemberRepositoryInterface $clanMemberRepository,
        PlayerRepositoryInterface $playerRepository,
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        NormalizerInterface $serializer,
    ) {
        $this->clanRepository = $clanRepository;
        $this->clanMemberRepository = $clanMemberRepository;
        $this->playerRepository = $playerRepository;
        $this->teamRepository = $teamRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->serializer = $serializer;
    }

    public function __invoke(FindClanQuery $findClanQuery): string
    {
        $clanId = new ClanId($findClanQuery->getClanId());

        $clan = $this->clanRepository->findOneBy(['id' => $clanId->getValue()]);
        if (!$clan instanceof Clan) {
            throw new NotFoundException('clan not found');
        }

        $memberships = $this->clanMemberRepository->findBy(['clan' => $clanId->getValue()], ['createdAt' => 'ASC']);
        usort(
            $memberships,
            static fn (ClanMember $one, ClanMember $two): int => (ClanRole::LEADER === $two->getRole()) <=> (ClanRole::LEADER === $one->getRole()),
        );

        $teams = $this->teamRepository->findBy(['clan' => $clanId->getValue()], ['size' => 'ASC', 'name' => 'ASC']);

        $lineups = [];
        $playerIds = array_map(static fn (ClanMember $membership): string => $membership->getPlayer()->getValue(), $memberships);
        if ([] !== $teams) {
            $teamPlayers = $this->teamPlayerRepository->findBy([
                'team' => array_map(static fn (Team $team): string => $team->getId()->getValue(), $teams),
            ]);
            foreach ($teamPlayers as $teamPlayer) {
                $lineups[$teamPlayer->getTeam()->getValue()][] = $teamPlayer->getPlayer()->getValue();
                $playerIds[] = $teamPlayer->getPlayer()->getValue();
            }
        }

        $players = [];
        if ([] !== $playerIds) {
            foreach ($this->playerRepository->findBy(['id' => array_values(array_unique($playerIds))]) as $player) {
                $players[$player->getId()->getValue()] = $player;
            }
        }

        /** @var array<string, mixed> $view */
        $view = $this->serializer->normalize($clan);
        $view['members'] = array_map(
            static fn (ClanMember $membership): array => ClanView::membership($membership, $players[$membership->getPlayer()->getValue()] ?? null),
            $memberships,
        );
        $view['teams'] = array_map(
            static fn (Team $team): array => TeamView::of($team, array_values(array_filter(array_map(
                static fn (string $playerId): ?Player => $players[$playerId] ?? null,
                $lineups[$team->getId()->getValue()] ?? [],
            )))),
            $teams,
        );

        return json_encode($view, JSON_THROW_ON_ERROR);
    }
}
