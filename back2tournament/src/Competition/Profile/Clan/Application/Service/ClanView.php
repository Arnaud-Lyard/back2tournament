<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Player\Domain\Entity\Player;

/**
 * How a place in a clan reads in the API: the player is named by battletag.
 */
final class ClanView
{
    /**
     * @return array<string, mixed>
     */
    public static function membership(ClanMember $membership, ?Player $player): array
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
