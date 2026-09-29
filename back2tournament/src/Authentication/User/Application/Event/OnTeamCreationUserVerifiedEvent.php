<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnTeamCreationUserVerifiedEvent extends Event
{
    private string $name;
    private string $user;
    private string $clan;
    private int $size;

    /** @var list<string> */
    private array $players;

    private string $leader;

    private string $createdTeam;

    /** @param list<string> $players */
    public function __construct(string $name, string $user, string $clan, int $size, array $players, string $leader)
    {
        $this->name = $name;
        $this->user = $user;
        $this->clan = $clan;
        $this->size = $size;
        $this->players = $players;
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
