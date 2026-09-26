<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnTeamCreationUserVerifiedEvent extends Event
{
    private string $name;
    private string $user;
    private string $player;
    private string $leader;

    public function __construct(string $name, string $user, string $player, string $leader)
    {
        $this->name = $name;
        $this->user = $user;
        $this->player = $player;
        $this->leader = $leader;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUser(): string
    {
        return $this->user;
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
