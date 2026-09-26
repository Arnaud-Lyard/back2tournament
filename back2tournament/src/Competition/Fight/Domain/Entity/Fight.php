<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Entity;

use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Event\FightCreatedEvent;
use App\Competition\Fight\Domain\Event\ResultCreatedEvent;
use App\Competition\Fight\Domain\Event\ResultUpdatedEvent;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Shared\Aggregate\AggregateRoot;

class Fight extends AggregateRoot
{
    private string $id;

    private string $competitorOne;

    private string $competitorTwo;

    private ?string $declaredBy = null;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function __construct(FightId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): ?FightId
    {
        return new FightId($this->id);
    }

    public function getCompetitorOne(): CompetitorId
    {
        return new CompetitorId($this->competitorOne);
    }

    public function setCompetitorOne(CompetitorId $competitorOne): self
    {
        $this->competitorOne = $competitorOne->getValue();
        return $this;
    }

    public function getCompetitorTwo(): CompetitorId
    {
        return new CompetitorId($this->competitorTwo);
    }

    public function setCompetitorTwo(CompetitorId $competitorTwo): self
    {
        $this->competitorTwo = $competitorTwo->getValue();
        return $this;
    }

    public function getDeclaredBy(): ?CompetitorId
    {
        return null === $this->declaredBy ? null : new CompetitorId($this->declaredBy);
    }

    public function setDeclaredBy(?CompetitorId $declaredBy): self
    {
        $this->declaredBy = $declaredBy?->getValue();

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
        FightId $fightId,
        CompetitorId $competitorOne,
        CompetitorId $competitorTwo,
    ): self {

        $fight = new self($fightId);
        $fight->setCompetitorOne($competitorOne);
        $fight->setCompetitorTwo($competitorTwo);
        $fight->setCreatedAt(new \DateTimeImmutable('now'));
        $fight->setUpdatedAt(new \DateTimeImmutable('now'));

        $fight->recordDomainEvent(new FightCreatedEvent($fightId));

        return $fight;
    }

    public static function createResult(
        Fight $fight,
        ResultId $resultId,
        CompetitorId $competitorId,
    ): Result {
        $result = new Result($resultId);
        $result->setFight($fight->getId());
        $result->setCompetitor($competitorId);
        $result->setScore(0);
        $result->setStatus(ResultStatus::PENDING);
        $result->setCreatedAt(new \DateTimeImmutable('now'));
        $result->setUpdatedAt(new \DateTimeImmutable('now'));

        $fight->recordDomainEvent(new ResultCreatedEvent($resultId));

        return $result;
    }

    public static function updateResult(
        Fight $fight,
        Result $result,
        int $score,
        CompetitorId $competitorId,
        ResultStatus $reportedStatus,
    ): Result
    {
        $result->setScore($score);
        $result->setStatus(ResultStatus::REPORTING);
        $result->setReportedStatus($reportedStatus);
        $result->setCompetitor($competitorId);
        $result->setUpdatedAt(new \DateTimeImmutable('now'));

        $fight->recordDomainEvent(new ResultUpdatedEvent($result->getId()));

        return $result;
    }

    public static function confirmResult(
        Fight $fight,
        Result $result,
        ResultStatus $status,
    ): Result
    {
        $result->setStatus($status);
        $result->setReportedStatus(null);
        $result->setUpdatedAt(new \DateTimeImmutable('now'));

        $fight->recordDomainEvent(new ResultUpdatedEvent($result->getId()));

        return $result;
    }
}
