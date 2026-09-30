<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Application\Model\AdmitClanMemberCommand;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class AdmitClanMemberHandler
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

    public function __invoke(AdmitClanMemberCommand $admitClanMemberCommand): string
    {
        $clanId = new ClanId($admitClanMemberCommand->getClanId());
        $playerId = new PlayerId($admitClanMemberCommand->getPlayerId());

        $clan = $this->clanRepository->findOneBy(['id' => $clanId->getValue()]);
        if (!$clan instanceof Clan) {
            throw new NotFoundException('clan not found');
        }

        $leader = $this->playerRepository->findOneBy(['id' => $clan->getLeader()->getValue()]);
        if (!$leader instanceof Player
            || $leader->getUser()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('only the clan leader accepts a request to join');
        }

        $membership = $this->clanMemberRepository->findOneBy([
            'clan' => $clanId->getValue(),
            'player' => $playerId->getValue(),
        ]);
        if (!$membership instanceof ClanMember) {
            throw new NotFoundException('this player did not ask to join the clan');
        }

        $elsewhere = $this->clanMemberRepository->findOneBy([
            'player' => $playerId->getValue(),
            'status' => ClanMemberStatus::ACTIVE,
        ]);
        if ($elsewhere instanceof ClanMember && $elsewhere->getClan()->getValue() !== $clanId->getValue()) {
            throw new ConflictException('this player already plays for another clan');
        }

        Clan::admit($clan, $membership);

        $this->clanMemberRepository->save($membership);
        $this->clanRepository->save($clan);

        foreach ($clan->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        $player = $this->playerRepository->findOneBy(['id' => $playerId->getValue()]);

        return json_encode($this->normalizeMembership($membership, $player instanceof Player ? $player : null), JSON_THROW_ON_ERROR);
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
