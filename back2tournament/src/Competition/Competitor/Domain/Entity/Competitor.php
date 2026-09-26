<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Domain\Entity;

use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Competitor\Domain\Event\CompetitorCreatedEvent;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Shared\Aggregate\AggregateRoot;

class Competitor extends AggregateRoot
{
    private string $id;

    private CompetitorType $type;

    private string $reference;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function __construct(CompetitorId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): ?CompetitorId
    {
        return new CompetitorId($this->id);
    }

    public function getType(): CompetitorType
    {
        return $this->type;
    }

    public function getReference(): string
    {
        return $this->reference;
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

    public static function create(CompetitorId $competitorId, CompetitorType $type, string $reference): self
    {
        $competitor = new self($competitorId);
        $competitor->type = $type;
        $competitor->reference = $reference;
        $competitor->setCreatedAt(new \DateTimeImmutable('now'));
        $competitor->setUpdatedAt(new \DateTimeImmutable('now'));

        $competitor->recordDomainEvent(new CompetitorCreatedEvent($competitorId));

        return $competitor;
    }
}
