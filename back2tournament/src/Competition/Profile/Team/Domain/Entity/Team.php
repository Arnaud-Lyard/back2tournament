<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Domain\Entity;

use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Event\TeamCreatedEvent;
use App\Competition\Profile\Team\Domain\Event\TeamDisbandedEvent;
use App\Competition\Profile\Team\Domain\Event\TeamPlayerCreatedEvent;
use App\Shared\Aggregate\AggregateRoot;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamNameValueObject;
use App\Shared\ValueObject\TeamSizeValueObject;

class Team extends AggregateRoot
{
    private string $id;

    private string $name;

    private string $clan;

    private string $game;

    private int $size;

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

    public function getClan(): ClanId
    {
        return new ClanId($this->clan);
    }

    public function getGame(): GameId
    {
        return new GameId($this->game);
    }

    public function getSize(): int
    {
        return $this->size;
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

    /** @param list<PlayerId> $players */
    public static function create(
        TeamId $teamId,
        TeamNameValueObject $name,
        ClanId $clanId,
        GameId $gameId,
        TeamSizeValueObject $size,
        PlayerId $leader,
        array $players,
    ): self {
        $lineup = array_map(static fn (PlayerId $player): string => $player->getValue(), $players);

        if (\count(array_unique($lineup)) !== \count($lineup)) {
            throw new ValidationException('a player appears twice in the lineup');
        }

        if (\count($lineup) !== $size->getValue()) {
            throw new ValidationException(\sprintf(
                'a %1$dv%1$d team fields exactly %1$d players, %2$d given',
                $size->getValue(),
                \count($lineup),
            ));
        }

        if (!\in_array($leader->getValue(), $lineup, true)) {
            throw new ValidationException('the team leader must play in the lineup');
        }

        $team = new self($teamId);
        $team->setName($name->getValue());
        $team->clan = $clanId->getValue();
        $team->game = $gameId->getValue();
        $team->size = $size->getValue();
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

    public static function disband(Team $team): self
    {
        $team->recordDomainEvent(new TeamDisbandedEvent(new TeamId($team->id)));

        return $team;
    }
}
