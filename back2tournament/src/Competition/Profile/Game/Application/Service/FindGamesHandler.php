<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Service;

use App\Competition\Profile\Game\Application\Model\FindGamesQuery;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindGamesHandler
{
    private GameRepositoryInterface $gameRepository;
    private NormalizerInterface $serializer;

    public function __construct(
        GameRepositoryInterface $gameRepository,
        NormalizerInterface $serializer,
    ) {
        $this->gameRepository = $gameRepository;
        $this->serializer = $serializer;
    }

    public function __invoke(FindGamesQuery $findGamesQuery): string
    {
        $games = $this->gameRepository->findBy([], ['title' => 'ASC', 'id' => 'ASC']);

        $normalized = [];
        foreach ($games as $game) {
            $normalized[] = $this->serializer->normalize($game);
        }

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
