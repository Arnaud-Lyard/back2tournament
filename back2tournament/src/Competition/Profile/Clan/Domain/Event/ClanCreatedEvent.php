<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Domain\Event;

use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ClanCreatedEvent extends Event implements DomainEventInterface
{
    protected \DateTimeImmutable $occur;
    protected ClanId $clanId;

    public function __construct(ClanId $clanId)
    {
        $this->clanId = $clanId;
        $this->occur = new \DateTimeImmutable();
    }

    public function getClanId(): ClanId
    {
        return $this->clanId;
    }

    public function getOccur(): \DateTimeImmutable
    {
        return $this->occur;
    }
}
