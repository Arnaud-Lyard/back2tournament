<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;

final class PlayerProfileProvider implements PlayerProfileProviderInterface
{
    private PlayerRepositoryInterface $playerRepository;

    public function __construct(PlayerRepositoryInterface $playerRepository)
    {
        $this->playerRepository = $playerRepository;
    }

    public function byUser(string $userId): array
    {
        /** @var list<Player> $players */
        $players = $this->playerRepository->findBy(['user' => $userId], ['createdAt' => 'ASC', 'id' => 'ASC']);

        $profiles = [];
        foreach ($players as $player) {
            $profiles[] = [
                'id' => $player->getId()->getValue(),
                'battletag' => (string) $player->getBattletag(),
                'game' => $player->getGame()->getValue(),
            ];
        }

        return $profiles;
    }
}
