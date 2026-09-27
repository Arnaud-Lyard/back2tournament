<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Model;

final class CreateTeamCommand
{
    private string $clan;

    private string $name;

    private int $size;

    /**
     * @var list<string>
     */
    private array $players;

    private string $leader;

    private string $user;

    /**
     * @param list<string> $players
     */
    public function __construct(string $clan, string $name, int $size, array $players, string $leader, string $user)
    {
        $this->clan = $clan;
        $this->name = $name;
        $this->size = $size;
        $this->players = $players;
        $this->leader = $leader;
        $this->user = $user;
    }

    public function getClan(): string
    {
        return $this->clan;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return list<string>
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getLeader(): string
    {
        return $this->leader;
    }

    /**
     * The caller, as the User context verified it.
     */
    public function getUser(): string
    {
        return $this->user;
    }
}
