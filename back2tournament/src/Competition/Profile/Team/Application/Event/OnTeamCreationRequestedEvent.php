<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnTeamCreationRequestedEvent extends Event
{
    private string $name;

    private string $clan;

    private int $size;

    /** @var list<string> */
    private array $players;

    private string $leader;

    private string $createdTeam;

    /** @param list<string> $players */
    public function __construct(string $name, string $clan, int $size, array $players, string $leader)
    {
        $this->name = $name;
        $this->clan = $clan;
        $this->size = $size;
        $this->players = $players;
        $this->leader = $leader;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClan(): string
    {
        return $this->clan;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    /** @return list<string> */
    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getLeader(): string
    {
        return $this->leader;
    }

    public function getCreatedTeam(): string
    {
        return $this->createdTeam;
    }

    public function setCreatedTeam(string $createdTeam): void
    {
        $this->createdTeam = $createdTeam;
    }
}
