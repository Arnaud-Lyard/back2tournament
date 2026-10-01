<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Application\Model\RemoveClanMemberCommand;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayer;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class RemoveClanMemberHandler
{
    private ClanRepositoryInterface $clanRepository;
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private PlayerRepositoryInterface $playerRepository;
    private TeamRepositoryInterface $teamRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ClanRepositoryInterface $clanRepository,
        ClanMemberRepositoryInterface $clanMemberRepository,
        PlayerRepositoryInterface $playerRepository,
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->clanRepository = $clanRepository;
        $this->clanMemberRepository = $clanMemberRepository;
        $this->playerRepository = $playerRepository;
        $this->teamRepository = $teamRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(RemoveClanMemberCommand $removeClanMemberCommand): string
    {
        $clanId = new ClanId($removeClanMemberCommand->getClanId());
        $playerId = new PlayerId($removeClanMemberCommand->getPlayerId());

        $clan = $this->clanRepository->findOneBy(['id' => $clanId->getValue(), 'dissolvedAt' => null]);
        if (!$clan instanceof Clan) {
            throw new NotFoundException('clan not found');
        }

        $membership = $this->clanMemberRepository->findOneBy([
            'clan' => $clanId->getValue(),
            'player' => $playerId->getValue(),
        ]);
        if (!$membership instanceof ClanMember) {
            throw new NotFoundException('this player has no place in the clan');
        }

        $caller = (string) $this->currentUserProvider->getUser()->getId();
        $player = $this->playerRepository->findOneBy(['id' => $playerId->getValue()]);
        $leader = $this->playerRepository->findOneBy(['id' => $clan->getLeader()->getValue()]);

        $isThePlayer = $player instanceof Player && $player->getUser()->getValue() === $caller;
        $isTheLeader = $leader instanceof Player && $leader->getUser()->getValue() === $caller;
        if (!$isThePlayer && !$isTheLeader) {
            throw new PermissionDeniedException('only the player or the clan leader ends this membership');
        }

        Clan::remove($clan, $membership);

        if (ClanMemberStatus::ACTIVE === $membership->getStatus() && $this->playsForClan($playerId, $clanId)) {
            throw new ConflictException('this player plays in a team of the clan: disband that team first');
        }

        $removed = json_encode(
            $this->normalizeMembership($membership, $player instanceof Player ? $player : null),
            JSON_THROW_ON_ERROR,
        );

        $this->clanMemberRepository->remove($membership);
        $this->clanRepository->save($clan);

        foreach ($clan->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $removed;
    }

    private function playsForClan(PlayerId $playerId, ClanId $clanId): bool
    {
        $teamIds = array_map(
            static fn (TeamPlayer $teamPlayer): string => $teamPlayer->getTeam()->getValue(),
            $this->teamPlayerRepository->findBy(['player' => $playerId->getValue()]),
        );

        return [] !== $teamIds
            && [] !== $this->teamRepository->findBy(['id' => $teamIds, 'clan' => $clanId->getValue()]);
    }

    /** @return array<string, mixed> */
    private function normalizeMembership(ClanMember $membership, ?Player $player): array
    {
        return [
            'id' => ['value' => $membership->getId()->getValue()],
            'clan' => ['value' => $membership->getClan()->getValue()],
            'player' => [
                'id' => ['value' => $membership->getPlayer()->getValue()],
                'battletag' => $player?->getBattletag(),
            ],
            'role' => $membership->getRole()->value,
            'status' => $membership->getStatus()->value,
            'createdAt' => $membership->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $membership->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
