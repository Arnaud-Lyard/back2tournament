<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Model;

final class CreateTeamCommand
{
    private string $name;

    private string $player;

    private string $leader;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPlayer(): string
    {
        return $this->player;
    }

    public function setPlayer(string $player): void
    {
        $this->player = $player;
    }

    public function getLeader(): string
    {
        return $this->leader;
    }

    public function setLeader(string $leader): void
    {
        $this->leader = $leader;
    }
}
