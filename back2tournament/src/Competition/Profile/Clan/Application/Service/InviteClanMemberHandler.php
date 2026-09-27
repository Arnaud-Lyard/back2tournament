<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Application\Model\InviteClanMemberCommand;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Entity\ClanMemberId;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class InviteClanMemberHandler
{
    private ClanRepositoryInterface $clanRepository;
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private PlayerRepositoryInterface $playerRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ClanRepositoryInterface $clanRepository,
        ClanMemberRepositoryInterface $clanMemberRepository,
        PlayerRepositoryInterface $playerRepository,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->clanRepository = $clanRepository;
        $this->clanMemberRepository = $clanMemberRepository;
        $this->playerRepository = $playerRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(InviteClanMemberCommand $inviteClanMemberCommand): string
    {
        $clanId = new ClanId($inviteClanMemberCommand->getClanId());
        $playerId = new PlayerId($inviteClanMemberCommand->getPlayerId());

        $clan = $this->clanRepository->findOneBy(['id' => $clanId->getValue()]);
        if (!$clan instanceof Clan) {
            throw new NotFoundException('clan not found');
        }

        $leader = $this->playerRepository->findOneBy(['id' => $clan->getLeader()->getValue()]);
        if (!$leader instanceof Player
            || $leader->getUser()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('only the clan leader invites players');
        }

        $player = $this->playerRepository->findOneBy(['id' => $playerId->getValue()]);
        if (!$player instanceof Player) {
            throw new NotFoundException('player not found');
        }

        if ($player->getGame()->getValue() !== $clan->getGame()->getValue()) {
            throw new ValidationException('this player plays another game than the clan');
        }

        if (null !== $this->clanMemberRepository->findOneBy([
            'clan' => $clanId->getValue(),
            'player' => $playerId->getValue(),
        ])) {
            throw new ConflictException('this player already is a member of the clan, or invited to it');
        }

        $membership = Clan::invite($clan, new ClanMemberId(Uuid::v4()->toString()), $playerId);

        $this->clanMemberRepository->save($membership);

        foreach ($clan->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return json_encode($this->normalizeMembership($membership, $player), JSON_THROW_ON_ERROR);
    }

    /**
     * A place in a clan, the player named by battletag.
     *
     * @return array<string, mixed>
     */
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
