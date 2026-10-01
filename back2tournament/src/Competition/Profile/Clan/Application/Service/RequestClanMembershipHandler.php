<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Application\Model\RequestClanMembershipCommand;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Entity\ClanMemberId;
use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class RequestClanMembershipHandler
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

    public function __invoke(RequestClanMembershipCommand $requestClanMembershipCommand): string
    {
        $clanId = new ClanId($requestClanMembershipCommand->getClanId());

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

        $existing = $this->clanMemberRepository->findOneBy([
            'clan' => $clanId->getValue(),
            'player' => $player->getId()->getValue(),
        ]);
        if ($existing instanceof ClanMember) {
            throw new ConflictException(match ($existing->getStatus()) {
                ClanMemberStatus::ACTIVE => 'you already are a member of this clan',
                ClanMemberStatus::INVITED => 'you are invited to this clan: accept the invitation',
                ClanMemberStatus::REQUESTED => 'you already asked to join this clan',
            });
        }

        $elsewhere = $this->clanMemberRepository->findOneBy([
            'player' => $player->getId()->getValue(),
            'status' => ClanMemberStatus::ACTIVE,
        ]);
        if ($elsewhere instanceof ClanMember) {
            throw new ConflictException('leave your current clan before asking to join another one');
        }

        $pending = $this->clanMemberRepository->findOneBy([
            'player' => $player->getId()->getValue(),
            'status' => ClanMemberStatus::REQUESTED,
        ]);
        if ($pending instanceof ClanMember) {
            throw new ConflictException('you already asked to join another clan: withdraw that request first');
        }

        $membership = Clan::request($clan, new ClanMemberId(Uuid::v4()->toString()), $player->getId());

        $this->clanMemberRepository->save($membership);

        foreach ($clan->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return json_encode($this->normalizeMembership($membership, $player), JSON_THROW_ON_ERROR);
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
