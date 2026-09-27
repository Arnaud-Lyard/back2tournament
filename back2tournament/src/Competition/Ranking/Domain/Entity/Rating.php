<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Domain\Entity;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Event\RatingCreatedEvent;
use App\Competition\Ranking\Domain\Event\RatingUpdatedEvent;
use App\Shared\Aggregate\AggregateRoot;

/**
 * The Elo rating of a player profile or a clan in one game, and the record of
 * the settled fights that made it.
 */
class Rating extends AggregateRoot
{
    /**
     * Where every player profile and every clan starts.
     */
    public const INITIAL = 1000;

    /**
     * The most a single fight can move a rating by.
     */
    public const K_FACTOR = 32;

    private string $id;

    private RankingSubject $subjectType;

    private string $subject;

    private string $game;

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

    /**
     * The id of the player profile or of the clan.
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getGame(): GameId
    {
        return new GameId($this->game);
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

    /**
     * A player profile or a clan enters the ranking of its game on its first
     * settled fight, at the initial rating.
     */
    public static function start(RatingId $ratingId, RankingSubject $subjectType, string $subject, GameId $gameId): self
    {
        $rating = new self($ratingId);
        $rating->subjectType = $subjectType;
        $rating->subject = $subject;
        $rating->game = $gameId->getValue();
        $rating->createdAt = new \DateTimeImmutable('now');
        $rating->updatedAt = $rating->createdAt;

        $rating->recordDomainEvent(new RatingCreatedEvent($ratingId));

        return $rating;
    }

    /**
     * A settled fight between the two. Both ratings move by the same number of
     * points, in opposite directions: the winner takes what the win was worth,
     * more when it was not expected, less when it was, and a draw lifts the
     * lower rating. `$winner` is null for a draw.
     *
     * @return array{RatingChange, RatingChange} the change of $one, then of $two
     */
    public static function settle(
        Rating $one,
        Rating $two,
        ?Rating $winner,
        FightId $fightId,
        RatingChangeId $changeOfOne,
        RatingChangeId $changeOfTwo,
    ): array {
        if ($one->id === $two->id) {
            throw new \InvalidArgumentException('a rating cannot fight itself');
        }
        if ($one->subjectType !== $two->subjectType || $one->game !== $two->game) {
            throw new \InvalidArgumentException('both ratings must belong to the same ranking');
        }
        if (null !== $winner && $winner !== $one && $winner !== $two) {
            throw new \InvalidArgumentException('the winner must be one of the two sides');
        }

        $scoreOfOne = match ($winner) {
            null => 0.5,
            $one => 1.0,
            default => 0.0,
        };
        $points = self::points($one->value, $two->value, $scoreOfOne);

        return [
            $one->move($points, $scoreOfOne, $fightId, $changeOfOne),
            $two->move(-$points, 1.0 - $scoreOfOne, $fightId, $changeOfTwo),
        ];
    }

    /**
     * What a fight is worth to a rating: K times the gap between the score it
     * made (1 for a win, 0.5 for a draw, 0 for a loss) and the score it was
     * expected to make against that opponent.
     */
    public static function points(int $rating, int $opponentRating, float $score): int
    {
        $expected = 1 / (1 + 10 ** (($opponentRating - $rating) / 400));

        return (int) round(self::K_FACTOR * ($score - $expected));
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
