<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Domain\Entity;

use App\Competition\Profile\Team\Domain\Entity\PlayerId;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Event\TeamCreatedEvent;
use App\Competition\Profile\Team\Domain\Event\TeamPlayerCreatedEvent;
use App\Shared\Aggregate\AggregateRoot;

class Team extends AggregateRoot
{
    private string $id;

    private string $name;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    private string $leader;

    public function __construct(TeamId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): ?TeamId
    {
        return new TeamId($this->id);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

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

    public function getLeader(): PlayerId
    {
        return new PlayerId($this->leader);
    }

    public function setLeader(PlayerId $leader): self
    {
        $this->leader = $leader->getValue();

        return $this;
    }

    public static function create(
        TeamId $teamId,
        string $name,
        PlayerId $leader,
    ): self {
        $team = new self($teamId);
        $team->setName($name);
        $team->setCreatedAt(new \DateTimeImmutable('now'));
        $team->setUpdatedAt(new \DateTimeImmutable('now'));
        $team->setLeader($leader);

        $team->recordDomainEvent(new TeamCreatedEvent($teamId));

        return $team;
    }

    public static function createTeamPlayer(Team $team, TeamPlayerId $teamPlayerId, PlayerId $playerId): TeamPlayer
    {
        $teamPlayer = new TeamPlayer($teamPlayerId);
        $teamPlayer->setTeam($team->getId());
        $teamPlayer->setPlayer($playerId);
        $teamPlayer->setCreatedAt(new \DateTimeImmutable('now'));
        $teamPlayer->setUpdatedAt(new \DateTimeImmutable('now'));

        $team->recordDomainEvent(new TeamPlayerCreatedEvent($teamPlayerId));

        return $teamPlayer;
    }
}
