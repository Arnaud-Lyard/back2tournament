<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Entity;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Ranking\Domain\Enum\FightOutcome;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Event\RatingCreatedEvent;
use App\Competition\Ranking\Domain\Event\RatingUpdatedEvent;
use App\Shared\Aggregate\AggregateRoot;
use App\Shared\ValueObject\TeamSizeValueObject;

class Rating extends AggregateRoot
{
    public const INITIAL = 1000;

    public const K_FACTOR = 32;

    private string $id;

    private RankingSubject $subjectType;

    private string $subject;

    private string $game;

    private int $teamSize;

    private int $value = self::INITIAL;

    private int $fights = 0;

    private int $wins = 0;

    private int $draws = 0;

    private int $losses = 0;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function __construct(RatingId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): RatingId
    {
        return new RatingId($this->id);
    }

    public function getSubjectType(): RankingSubject
    {
        return $this->subjectType;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getGame(): GameId
    {
        return new GameId($this->game);
    }

    public function getTeamSize(): int
    {
        return $this->teamSize;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getFights(): int
    {
        return $this->fights;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function getDraws(): int
    {
        return $this->draws;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public static function start(RatingId $ratingId, RankingSubject $subjectType, string $subject, GameId $gameId, TeamSizeValueObject $teamSize): self
    {
        $rating = new self($ratingId);
        $rating->subjectType = $subjectType;
        $rating->subject = $subject;
        $rating->game = $gameId->getValue();
        $rating->teamSize = $teamSize->getValue();
        $rating->createdAt = new \DateTimeImmutable('now');
        $rating->updatedAt = $rating->createdAt;

        $rating->recordDomainEvent(new RatingCreatedEvent($ratingId));

        return $rating;
    }

    /**
     * @param list<Rating> $sideOne
     * @param list<Rating> $sideTwo
     * @param list<RatingChangeId> $changeIds
     *
     * @return list<RatingChange>
     */
    public static function settle(array $sideOne, array $sideTwo, FightOutcome $outcome, FightId $fightId, array $changeIds): array
    {
        if ([] === $sideOne || [] === $sideTwo) {
            throw new \InvalidArgumentException('each side needs at least one rating');
        }

        $ratings = [...$sideOne, ...$sideTwo];
        if (\count($changeIds) !== \count($ratings)) {
            throw new \InvalidArgumentException('each rating needs a change of its own');
        }

        $seen = [];
        foreach ($ratings as $rating) {
            if (isset($seen[$rating->id])) {
                throw new \InvalidArgumentException('a rating cannot stand twice in one fight');
            }
            $seen[$rating->id] = true;

            if ($rating->subjectType !== $ratings[0]->subjectType
                || $rating->game !== $ratings[0]->game
                || $rating->teamSize !== $ratings[0]->teamSize) {
                throw new \InvalidArgumentException('every rating must belong to the same ranking');
            }
        }

        $scoreOfOne = $outcome->scoreOfSideOne();
        $points = self::points(self::average($sideOne), self::average($sideTwo), $scoreOfOne);

        $changes = [];
        foreach ($sideOne as $rating) {
            $changes[] = $rating->move($points, $scoreOfOne, $fightId, $changeIds[\count($changes)]);
        }
        foreach ($sideTwo as $rating) {
            $changes[] = $rating->move(-$points, 1.0 - $scoreOfOne, $fightId, $changeIds[\count($changes)]);
        }

        return $changes;
    }

    public static function points(float $rating, float $opponentRating, float $score): int
    {
        $expected = 1 / (1 + 10 ** (($opponentRating - $rating) / 400));

        return (int) round(self::K_FACTOR * ($score - $expected));
    }

    /** @param list<Rating> $side */
    private static function average(array $side): float
    {
        return array_sum(array_map(static fn (Rating $rating): int => $rating->value, $side)) / \count($side);
    }

    private function move(int $points, float $score, FightId $fightId, RatingChangeId $changeId): RatingChange
    {
        $before = $this->value;

        $this->value += $points;
        ++$this->fights;
        match ($score) {
            1.0 => ++$this->wins,
            0.0 => ++$this->losses,
            default => ++$this->draws,
        };
        $this->updatedAt = new \DateTimeImmutable('now');

        $this->recordDomainEvent(new RatingUpdatedEvent($this->getId()));

        return new RatingChange($changeId, $this->getId(), $fightId, $before, $this->value);
    }
}
