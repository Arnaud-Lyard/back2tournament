<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Service;

use App\Competition\Profile\Player\Application\Model\FindGamePlayersQuery;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindGamePlayersHandler
{
    private PlayerRepositoryInterface $playerRepository;
    private NormalizerInterface $serializer;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        NormalizerInterface $serializer,
    ) {
        $this->playerRepository = $playerRepository;
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

        $normalized = [];
        foreach ($players as $player) {
            $normalized[] = $this->serializer->normalize($player);
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
