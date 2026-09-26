<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Domain\Entity;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;

/**
 * One slot of the bracket: two competitors meet in round `round`, at `position`
 * counted from the top. A side stays empty until the matchup feeding it is
 * decided; the fight is opened once both sides are known.
 */
class Matchup
{
    private string $id;

    private string $tournament;

    private int $round;

    private int $position;

    private ?string $competitorOne = null;

    private ?string $competitorTwo = null;

    private ?string $fight = null;

    private ?string $winner = null;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function __construct(MatchupId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): MatchupId
    {
        return new MatchupId($this->id);
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

    public function getRound(): int
    {
        return $this->round;
    }

    public function setRound(int $round): self
    {
        $this->round = $round;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getCompetitorOne(): ?CompetitorId
    {
        return null === $this->competitorOne ? null : new CompetitorId($this->competitorOne);
    }

    public function setCompetitorOne(?CompetitorId $competitorOne): self
    {
        $this->competitorOne = $competitorOne?->getValue();

        return $this;
    }

    public function getCompetitorTwo(): ?CompetitorId
    {
        return null === $this->competitorTwo ? null : new CompetitorId($this->competitorTwo);
    }

    public function setCompetitorTwo(?CompetitorId $competitorTwo): self
    {
        $this->competitorTwo = $competitorTwo?->getValue();

        return $this;
    }

    public function getFight(): ?FightId
    {
        return null === $this->fight ? null : new FightId($this->fight);
    }

    public function setFight(?FightId $fight): self
    {
        $this->fight = $fight?->getValue();

        return $this;
    }

    public function getWinner(): ?CompetitorId
    {
        return null === $this->winner ? null : new CompetitorId($this->winner);
    }

    public function setWinner(?CompetitorId $winner): self
    {
        $this->winner = $winner?->getValue();

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
