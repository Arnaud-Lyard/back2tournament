<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Domain\Entity;

use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Event\PlayerCreatedEvent;
use App\Competition\Profile\Player\Domain\Event\PlayerDeletedEvent;
use App\Competition\Profile\Player\Domain\Event\PlayerUpdatedEvent;
use App\Shared\Aggregate\AggregateRoot;

class Player extends AggregateRoot
{
    private string $id;

    private string $battletag;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    private string $game;

    private string $user;

    public function __construct(PlayerId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): ?PlayerId
    {
        return new PlayerId($this->id);
    }

    public function getBattletag(): ?string
    {
        return $this->battletag;
    }

    public function setBattletag(?string $battletag): self
    {
        $this->battletag = $battletag;

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

    public function getGame(): GameId
    {
        return new GameId($this->game);
    }

    public function setGame(GameId $game): self
    {
        $this->game = $game->getValue();

        return $this;
    }

    public function getUser(): UserId
    {
        return new UserId($this->user);
    }

    public function setUser(UserId $user): self
    {
        $this->user = $user->getValue();

        return $this;
    }

    public static function create(
        PlayerId $playerId,
        string $battletag,
        GameId $gameId,
        UserId $userId
    ): self {
        $player = new self($playerId);
        $player->setBattletag($battletag);
        $player->setCreatedAt(new \DateTimeImmutable('now'));
        $player->setUpdatedAt(new \DateTimeImmutable('now'));
        $player->setUser($userId);
        $player->setGame($gameId);

        $player->recordDomainEvent(new PlayerCreatedEvent($playerId));

        return $player;
    }

    public static function update(Player $player, string $battletag): self
    {
        $player->setBattletag($battletag);
        $player->setUpdatedAt(new \DateTimeImmutable('now'));

        $player->recordDomainEvent(new PlayerUpdatedEvent(new PlayerId($player->id)));

        return $player;
    }

    public static function delete(Player $player): self
    {
        $player->recordDomainEvent(new PlayerDeletedEvent(new PlayerId($player->id)));

        return $player;
    }
}
