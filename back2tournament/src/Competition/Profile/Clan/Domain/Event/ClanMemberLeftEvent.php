<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Domain\Event;

use App\Competition\Profile\Clan\Domain\Entity\ClanMemberId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ClanMemberLeftEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected ClanMemberId $clanMemberId;

    public function __construct(ClanMemberId $clanMemberId)
    {
        $this->clanMemberId = $clanMemberId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getClanMemberId(): ClanMemberId
    {
        return $this->clanMemberId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
