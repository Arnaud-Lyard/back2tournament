<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Competition\Profile\Clan\Application\Model\FindGameClansQuery;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindGameClansHandler
{
    private ClanRepositoryInterface $clanRepository;
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private NormalizerInterface $serializer;

    public function __construct(
        ClanRepositoryInterface $clanRepository,
        ClanMemberRepositoryInterface $clanMemberRepository,
        NormalizerInterface $serializer,
    ) {
        $this->clanRepository = $clanRepository;
        $this->clanMemberRepository = $clanMemberRepository;
        $this->serializer = $serializer;
    }

    public function __invoke(FindGameClansQuery $findGameClansQuery): string
    {
        $gameId = new GameId($findGameClansQuery->getGameId())->getValue();
        $search = $findGameClansQuery->getSearch();
        $limit = $findGameClansQuery->getLimit();

        $clans = $this->clanRepository->findPage($gameId, $search, $limit, $findGameClansQuery->getOffset());

        $members = $this->clanMemberRepository->countActiveByClan(
            array_map(static fn (Clan $clan): string => $clan->getId()->getValue(), $clans)
        );

        $items = [];
        foreach ($clans as $clan) {
            /** @var array<string, mixed> $item */
            $item = $this->serializer->normalize($clan);
            $item['members'] = $members[$clan->getId()->getValue()] ?? 0;
            $items[] = $item;
        }

        $total = $this->clanRepository->countPage($gameId, $search);

        return json_encode([
            'items' => $items,
            'total' => $total,
            'page' => $findGameClansQuery->getPage(),
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ], JSON_THROW_ON_ERROR);
    }
}
