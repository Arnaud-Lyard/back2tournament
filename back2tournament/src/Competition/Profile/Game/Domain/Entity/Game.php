<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Domain\Entity;

use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Event\GameCreatedEvent;
use App\Shared\Aggregate\AggregateRoot;

class Game extends AggregateRoot
{
    private string $id;

    private string $title;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function __construct(GameId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): ?GameId
    {
        return new GameId($this->id);
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

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

    public static function create(
        GameId $gameId,
        string $title,
    ): self {
        $game = new self($gameId);
        $game->setTitle($title);
        $game->setCreatedAt(new \DateTimeImmutable('now'));
        $game->setUpdatedAt(new \DateTimeImmutable('now'));

        $game->recordDomainEvent(new GameCreatedEvent($gameId));

        return $game;
    }
}
