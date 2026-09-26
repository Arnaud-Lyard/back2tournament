<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Domain\Entity;

use App\Competition\Profile\Team\Domain\Entity\PlayerId;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayerId;

class TeamPlayer
{
    private string $id;

    private string $team;

    private string $player;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function __construct(TeamPlayerId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): TeamPlayerId
    {
        return new TeamPlayerId($this->id);
    }

    public function getTeam(): TeamId
    {
        return new TeamId($this->team);
    }

    public function setTeam(TeamId $team): self
    {
        $this->team = $team->getValue();

        return $this;
    }

    public function getPlayer(): PlayerId
    {
        return new PlayerId($this->player);
    }

    public function setPlayer(PlayerId $player): self
    {
        $this->player = $player->getValue();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
