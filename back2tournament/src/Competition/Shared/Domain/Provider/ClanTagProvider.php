<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;

final class ClanTagProvider implements ClanTagProviderInterface
{
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private ClanRepositoryInterface $clanRepository;

    public function __construct(
        ClanMemberRepositoryInterface $clanMemberRepository,
        ClanRepositoryInterface $clanRepository,
    ) {
        $this->clanMemberRepository = $clanMemberRepository;
        $this->clanRepository = $clanRepository;
    }

    public function clansOfPlayers(array $playerIds): array
    {
        $playerIds = array_values(array_unique($playerIds));
        if ([] === $playerIds) {
            return [];
        }

        $clanOf = [];
        foreach ($this->clanMemberRepository->findBy(['player' => $playerIds, 'status' => ClanMemberStatus::ACTIVE]) as $membership) {
            $clanOf[$membership->getPlayer()->getValue()] = $membership->getClan()->getValue();
        }

        $tags = $this->tagsOfClans(array_values(array_unique($clanOf)));

        $clans = [];
        foreach ($clanOf as $playerId => $clanId) {
            if (isset($tags[$clanId])) {
                $clans[$playerId] = ['id' => $clanId, 'tag' => $tags[$clanId]];
            }
        }

        return $clans;
    }

    public function tagsOfClans(array $clanIds): array
    {
        $clanIds = array_values(array_unique($clanIds));
        if ([] === $clanIds) {
            return [];
        }

        $tags = [];
        foreach ($this->clanRepository->findBy(['id' => $clanIds]) as $clan) {
            $tags[$clan->getId()->getValue()] = $clan->getTag();
        }

        return $tags;
    }
}
