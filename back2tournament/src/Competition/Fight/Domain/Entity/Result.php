<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Entity;

use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;

class Result
{
    private string $id;

    private string $fight;

    private string $competitor;

    private ?string $clan = null;

    private int $score;

    private ResultStatus $status;

    private ?ResultStatus $reportedStatus = null;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function __construct(ResultId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): ResultId
    {
        return new ResultId($this->id);
    }

    public function getFight(): FightId
    {
        return new FightId($this->fight);
    }

    public function setFight(FightId $fight): self
    {
        $this->fight = $fight->getValue();

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

    public function getClan(): ?ClanId
    {
        return null === $this->clan ? null : new ClanId($this->clan);
    }

    public function setClan(?ClanId $clan): self
    {
        $this->clan = $clan?->getValue();

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getStatus(): ResultStatus
    {
        return $this->status;
    }

    public function setStatus(ResultStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getReportedStatus(): ?ResultStatus
    {
        return $this->reportedStatus;
    }

    public function setReportedStatus(?ResultStatus $reportedStatus): self
    {
        $this->reportedStatus = $reportedStatus;

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
