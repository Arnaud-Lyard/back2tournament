<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Domain\Entity;

use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;

/**
 * A competitor registered in a tournament. The seed is its registration rank:
 * 1 registered first, and the bracket pairs seeds from both ends.
 */
class Participant
{
    private string $id;

    private string $tournament;

    private string $competitor;

    private int $seed;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function __construct(ParticipantId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): ParticipantId
    {
        return new ParticipantId($this->id);
    }

    public function getTournament(): TournamentId
    {
        return new TournamentId($this->tournament);
    }

    public function setTournament(TournamentId $tournament): self
    {
        $this->tournament = $tournament->getValue();

        return $this;
    }

    public function getCompetitor(): CompetitorId
    {
        return new CompetitorId($this->competitor);
    }

    public function setCompetitor(CompetitorId $competitor): self
    {
        $this->competitor = $competitor->getValue();

        return $this;
    }

    public function getSeed(): int
    {
        return $this->seed;
    }

    public function setSeed(int $seed): self
    {
        $this->seed = $seed;

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
