<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Entity;

use App\Competition\Fight\Domain\Entity\FightId;

/**
 * What one settled fight did to one rating. A fight moves each rating once:
 * its changes are also the proof that it was counted.
 */
class RatingChange
{
    private string $id;

    private string $rating;

    private string $fight;

    private int $before;

    private int $after;

    private \DateTimeImmutable $createdAt;

    public function __construct(RatingChangeId $id, RatingId $rating, FightId $fight, int $before, int $after)
    {
        $this->id = $id->getValue();
        $this->rating = $rating->getValue();
        $this->fight = $fight->getValue();
        $this->before = $before;
        $this->after = $after;
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): RatingChangeId
    {
        return new RatingChangeId($this->id);
    }

    public function getRating(): RatingId
    {
        return new RatingId($this->rating);
    }

    public function getFight(): FightId
    {
        return new FightId($this->fight);
    }

    public function getBefore(): int
    {
        return $this->before;
    }

    public function getAfter(): int
    {
        return $this->after;
    }

    /**
     * The points won, or lost when negative.
     */
    public function getPoints(): int
    {
        return $this->after - $this->before;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
