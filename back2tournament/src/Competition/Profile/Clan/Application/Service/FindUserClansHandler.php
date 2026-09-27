<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Application\Model\FindUserClansQuery;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindUserClansHandler
{
    private ClanRepositoryInterface $clanRepository;
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private PlayerRepositoryInterface $playerRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private NormalizerInterface $serializer;

    public function __construct(
        ClanRepositoryInterface $clanRepository,
        ClanMemberRepositoryInterface $clanMemberRepository,
        PlayerRepositoryInterface $playerRepository,
        CurrentUserProviderInterface $currentUserProvider,
        NormalizerInterface $serializer,
    ) {
        $this->clanRepository = $clanRepository;
        $this->clanMemberRepository = $clanMemberRepository;
        $this->playerRepository = $playerRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->serializer = $serializer;
    }

    public function __invoke(FindUserClansQuery $findUserClansQuery): string
    {
        $players = [];
        foreach ($this->playerRepository->findBy(['user' => (string) $this->currentUserProvider->getUser()->getId()]) as $player) {
            $players[$player->getId()->getValue()] = $player;
        }

        if ([] === $players) {
            return json_encode([], JSON_THROW_ON_ERROR);
        }

        $memberships = $this->clanMemberRepository->findBy(['player' => array_keys($players)], ['createdAt' => 'ASC']);
        if ([] === $memberships) {
            return json_encode([], JSON_THROW_ON_ERROR);
        }

        $clans = [];
        $clanIds = array_map(static fn (ClanMember $membership): string => $membership->getClan()->getValue(), $memberships);
        foreach ($this->clanRepository->findBy(['id' => $clanIds]) as $clan) {
            $clans[$clan->getId()->getValue()] = $clan;
        }

        $items = [];
        foreach ($memberships as $membership) {
            $clan = $clans[$membership->getClan()->getValue()] ?? null;
            if (!$clan instanceof Clan) {
                continue;
            }

            $items[] = [
                'clan' => $this->serializer->normalize($clan),
                'membership' => $this->normalizeMembership($membership, $players[$membership->getPlayer()->getValue()] ?? null),
            ];
        }

        return json_encode($items, JSON_THROW_ON_ERROR);
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
