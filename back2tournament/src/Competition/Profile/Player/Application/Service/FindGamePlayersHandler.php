<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Service;

use App\Competition\Profile\Player\Application\Model\FindGamePlayersQuery;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Shared\Domain\Provider\ClanTagProviderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindGamePlayersHandler
{
    private PlayerRepositoryInterface $playerRepository;
    private ClanTagProviderInterface $clanTagProvider;
    private NormalizerInterface $serializer;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        ClanTagProviderInterface $clanTagProvider,
        NormalizerInterface $serializer,
    ) {
        $this->playerRepository = $playerRepository;
        $this->clanTagProvider = $clanTagProvider;
        $this->serializer = $serializer;
    }

    public function __invoke(FindGamePlayersQuery $findGamePlayersQuery): string
    {
        $gameId = $findGamePlayersQuery->getGameId();
        $search = $findGamePlayersQuery->getSearch();
        $limit = $findGamePlayersQuery->getLimit();

        $players = $this->playerRepository->findPage(
            $gameId,
            $search,
            $limit,
            $findGamePlayersQuery->getOffset(),
        );

        // The tag of the clan each profile plays for, shown next to its battletag.
        $clans = $this->clanTagProvider->clansOfPlayers(array_map(
            static fn ($player): string => $player->getId()->getValue(),
            $players,
        ));

        $normalized = [];
        foreach ($players as $player) {
            $item = $this->serializer->normalize($player);
            $item['clanTag'] = $clans[$player->getId()->getValue()]['tag'] ?? null;
            $normalized[] = $item;
        }

        $total = $this->playerRepository->countPage($gameId, $search);

        return json_encode([
            'items' => $normalized,
            'total' => $total,
            'page' => $findGamePlayersQuery->getPage(),
            'limit' => $limit,
            'pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
        ], JSON_THROW_ON_ERROR);
    }
}
