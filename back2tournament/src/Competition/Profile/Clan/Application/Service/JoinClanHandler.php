<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Application\Model\JoinClanCommand;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class JoinClanHandler
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

    public function __invoke(JoinClanCommand $joinClanCommand): string
    {
        $clanId = new ClanId($joinClanCommand->getClanId());

        $clan = $this->clanRepository->findOneBy(['id' => $clanId->getValue(), 'dissolvedAt' => null]);
        if (!$clan instanceof Clan) {
            throw new NotFoundException('clan not found');
        }

        $player = $this->playerRepository->findOneBy([
            'user' => (string) $this->currentUserProvider->getUser()->getId(),
            'game' => $clan->getGame()->getValue(),
        ]);
        if (!$player instanceof Player) {
            throw new NotFoundException('you hold no player profile in this game');
        }

        $membership = $this->clanMemberRepository->findOneBy([
            'clan' => $clanId->getValue(),
            'player' => $player->getId()->getValue(),
        ]);
        if (!$membership instanceof ClanMember) {
            throw new NotFoundException('you have not been invited to this clan');
        }

        $elsewhere = $this->clanMemberRepository->findOneBy([
            'player' => $player->getId()->getValue(),
            'status' => ClanMemberStatus::ACTIVE,
        ]);
        if ($elsewhere instanceof ClanMember && $elsewhere->getClan()->getValue() !== $clanId->getValue()) {
            throw new ConflictException('leave your current clan before joining another one');
        }

        Clan::join($clan, $membership);

        $this->clanMemberRepository->save($membership);
        $this->clanRepository->save($clan);

        foreach ($clan->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        $this->withdrawPendingRequest($player);

        return json_encode($this->normalizeMembership($membership, $player), JSON_THROW_ON_ERROR);
    }

    private function withdrawPendingRequest(Player $player): void
    {
        $request = $this->clanMemberRepository->findOneBy([
            'player' => $player->getId()->getValue(),
            'status' => ClanMemberStatus::REQUESTED,
        ]);
        if (!$request instanceof ClanMember) {
            return;
        }

        $requestedClan = $this->clanRepository->findOneBy(['id' => $request->getClan()->getValue()]);
        if (!$requestedClan instanceof Clan) {
            return;
        }

        Clan::remove($requestedClan, $request);

        $this->clanMemberRepository->remove($request);
        $this->clanRepository->save($requestedClan);

        foreach ($requestedClan->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }
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
