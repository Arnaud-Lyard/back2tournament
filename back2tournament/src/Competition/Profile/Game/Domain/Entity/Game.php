<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Domain\Entity;

use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Event\GameCreatedEvent;
use App\Competition\Profile\Game\Domain\Event\GameUpdatedEvent;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Shared\Aggregate\AggregateRoot;
use App\Shared\Exception\ValidationException;

class Game extends AggregateRoot
{
    private string $id;

    private string $title;

    /**
     * @var list<int> the formats this game is played in: 1 for 1v1, 5 for 5v5
     */
    private array $teamSizes = [1];

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

    /**
     * @return list<int>
     */
    public function getTeamSizes(): array
    {
        return $this->teamSizes;
    }

    /**
     * @param list<TeamSize> $teamSizes
     */
    public function setTeamSizes(array $teamSizes): self
    {
        if ([] === $teamSizes) {
            throw new ValidationException('A game must be played in at least one format');
        }

        $sizes = array_values(array_unique(array_map(
            static fn (TeamSize $teamSize): int => $teamSize->getValue(),
            $teamSizes,
        )));
        sort($sizes);

        $this->teamSizes = $sizes;

        return $this;
    }

    public function supportsTeamSize(int $teamSize): bool
    {
        return \in_array($teamSize, $this->teamSizes, true);
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

    /**
     * @param list<TeamSize> $teamSizes
     */
    public static function create(
        GameId $gameId,
        string $title,
        array $teamSizes,
    ): self {
        if ('' === trim($title)) {
            throw new ValidationException('A game title cannot be empty');
        }

        $game = new self($gameId);
        $game->setTitle($title);
        $game->setTeamSizes($teamSizes);
        $game->setCreatedAt(new \DateTimeImmutable('now'));
        $game->setUpdatedAt(new \DateTimeImmutable('now'));

        $game->recordDomainEvent(new GameCreatedEvent($gameId));

        return $game;
    }

    /**
     * @param list<TeamSize>|null $teamSizes null keeps the formats as they are
     */
    public static function update(Game $game, ?string $title, ?array $teamSizes): self
    {
        if (null !== $title) {
            if ('' === trim($title)) {
                throw new ValidationException('A game title cannot be empty');
            }
            $game->setTitle($title);
        }

        if (null !== $teamSizes) {
            $game->setTeamSizes($teamSizes);
        }

        $game->setUpdatedAt(new \DateTimeImmutable('now'));

        $game->recordDomainEvent(new GameUpdatedEvent(new GameId($game->id)));

        return $game;
    }
}
