<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Service;

use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Team\Domain\Entity\Team;

/**
 * How a team reads in the API: the lineup is named by battletag, leader first.
 */
final class TeamView
{
    /**
     * @param list<Player> $players the lineup
     *
     * @return array<string, mixed>
     */
    public static function of(Team $team, array $players): array
    {
        $leader = $team->getLeader()->getValue();
        usort(
            $players,
            static fn (Player $one, Player $two): int => ($two->getId()->getValue() === $leader) <=> ($one->getId()->getValue() === $leader),
        );

        return [
            'id' => ['value' => $team->getId()->getValue()],
            'name' => $team->getName(),
            'clan' => ['value' => $team->getClan()->getValue()],
            'game' => ['value' => $team->getGame()->getValue()],
            'size' => $team->getSize(),
            'leader' => ['value' => $leader],
            'players' => array_map(static fn (Player $player): array => [
                'id' => ['value' => $player->getId()->getValue()],
                'battletag' => $player->getBattletag(),
            ], $players),
            'createdAt' => $team->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $team->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
