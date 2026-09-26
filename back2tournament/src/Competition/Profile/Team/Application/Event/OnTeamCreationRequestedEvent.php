<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnTeamCreationRequestedEvent extends Event
{
    private string $name;

    private string $player;

    private string $leader;

    public function __construct(string $name, string $player, string $leader)
    {
        $this->name = $name;
        $this->player = $player;
        $this->leader = $leader;
    }

    public function getName(): string
    {
        return $this->name;
    }


    public function getPlayer(): string
    {
        return $this->player;
    }

    public function getLeader(): string
    {
        return $this->leader;
    }
}
