<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Service;

use App\Competition\Profile\Player\Application\Model\FindPlayerQuery;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindPlayerHandler
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

    public function __invoke(FindPlayerQuery $findPlayerQuery): string
    {
        $player = $this->playerRepository->findOneBy(['id' => $findPlayerQuery->getPlayerId()]);
        if (!$player instanceof Player) {
            throw new NotFoundException('player not found');
        }

        return json_encode($this->serializer->normalize($player), JSON_THROW_ON_ERROR);
    }
}
