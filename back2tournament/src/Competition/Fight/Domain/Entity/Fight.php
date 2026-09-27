<?php

declare(strict_types=1);

namespace App\Competition\Fight\Domain\Entity;

use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Event\FightCreatedEvent;
use App\Competition\Fight\Domain\Event\FightSettledEvent;
use App\Competition\Fight\Domain\Event\ResultCreatedEvent;
use App\Competition\Fight\Domain\Event\ResultUpdatedEvent;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Shared\Aggregate\AggregateRoot;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamSizeValueObject;

/**
 * Two competitors, of the same game and format, and the outcome they agree on:
 * one side declares the scores, the other confirms them.
 */
class Fight extends AggregateRoot
{
    private string $id;

    private string $competitorOne;

    private string $competitorTwo;

    private string $game;

    private int $teamSize = 1;

    private ?string $tournament = null;

    private ?string $declaredBy = null;

    /**
     * An administrator settled the fight, after a dispute between its sides.
     */
    private bool $arbitrated = false;

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

    public function getGame(): GameId
    {
        return new GameId($this->game);
    }

    public function getTeamSize(): int
    {
        return $this->teamSize;
    }

    public function getTournament(): ?TournamentId
    {
        return null === $this->tournament ? null : new TournamentId($this->tournament);
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

    public function isArbitrated(): bool
    {
        return $this->arbitrated;
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
     * The other side of the fight, or null when $side is not one of its two.
     */
    public function opponentOf(CompetitorId $side): ?CompetitorId
    {
        return match ($side->getValue()) {
            $this->competitorOne => $this->getCompetitorTwo(),
            $this->competitorTwo => $this->getCompetitorOne(),
            default => null,
        };
    }

    /**
     * The side of this fight among the competitors someone speaks for, or null
     * when they speak for neither.
     *
     * @param list<string> $competitorIds
     */
    public function sideAmong(array $competitorIds): ?CompetitorId
    {
        $sides = array_values(array_intersect([$this->competitorOne, $this->competitorTwo], $competitorIds));

        if (\count($sides) > 1) {
            throw new ConflictException('you stand on both sides of this fight');
        }

        return [] === $sides ? null : new CompetitorId($sides[0]);
    }

    public static function create(
        FightId $fightId,
        CompetitorId $competitorOne,
        CompetitorId $competitorTwo,
        GameId $gameId,
        TeamSizeValueObject $teamSize,
        ?TournamentId $tournamentId = null,
    ): self {
        if ($competitorOne->getValue() === $competitorTwo->getValue()) {
            throw new ValidationException('a competitor cannot fight itself');
        }

        $fight = new self($fightId);
        $fight->setCompetitorOne($competitorOne);
        $fight->setCompetitorTwo($competitorTwo);
        $fight->game = $gameId->getValue();
        $fight->teamSize = $teamSize->getValue();
        $fight->tournament = $tournamentId?->getValue();
        $fight->setCreatedAt(new \DateTimeImmutable('now'));
        $fight->setUpdatedAt(new \DateTimeImmutable('now'));

        $fight->recordDomainEvent(new FightCreatedEvent($fightId));

        return $fight;
    }

    /**
     * The pending result of one side, which records the clan the side plays
     * for as the fight opens; null for a profile in no clan.
     */
    public static function createResult(
        Fight $fight,
        ResultId $resultId,
        CompetitorId $competitorId,
        ?ClanId $clan = null,
    ): Result {
        $result = new Result($resultId);
        $result->setFight($fight->getId());
        $result->setCompetitor($competitorId);
        $result->setClan($clan);
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

    /**
     * One side declares the scores of both. The outcome follows from them:
     * the higher score wins, equal scores draw. Until the other side confirms,
     * the declaring side may correct its declaration; the other side may not
     * overwrite it.
     */
    public static function declareOutcome(
        Fight $fight,
        CompetitorId $declaringSide,
        Result $declaring,
        Score $declaringScore,
        Result $opposing,
        Score $opposingScore,
    ): void {
        $opposingSide = $fight->opponentOf($declaringSide);
        if (null === $opposingSide) {
            throw new PermissionDeniedException('you do not take part in this fight');
        }

        self::ensureResultOf($fight, $declaring, $declaringSide);
        self::ensureResultOf($fight, $opposing, $opposingSide);

        foreach ([$declaring, $opposing] as $result) {
            if (!\in_array($result->getStatus(), [ResultStatus::PENDING, ResultStatus::REPORTING], true)) {
                throw new ConflictException('this fight is already settled');
            }
        }

        if (null !== $fight->declaredBy && $fight->declaredBy !== $declaringSide->getValue()) {
            throw new ConflictException('the other side already declared an outcome: confirm it, or settle the disagreement with an admin');
        }

        $declaringStatus = self::outcome($declaringScore->getValue(), $opposingScore->getValue());
        if (null !== $fight->tournament && ResultStatus::DRAW === $declaringStatus) {
            throw new ValidationException('a tournament fight cannot end in a draw');
        }

        self::updateResult($fight, $declaring, $declaringScore->getValue(), $declaringSide, $declaringStatus);
        self::updateResult($fight, $opposing, $opposingScore->getValue(), $opposingSide, self::outcome($opposingScore->getValue(), $declaringScore->getValue()));

        $fight->setDeclaredBy($declaringSide);
        $fight->setUpdatedAt(new \DateTimeImmutable('now'));
    }

    /**
     * The side that did not declare agrees: both results settle on the
     * outcome that was declared.
     */
    public static function confirmOutcome(
        Fight $fight,
        CompetitorId $confirmingSide,
        Result $resultOne,
        Result $resultTwo,
    ): void {
        if (null === $fight->declaredBy) {
            throw new ConflictException('no outcome has been declared on this fight yet');
        }

        $opposingSide = $fight->opponentOf($confirmingSide);
        if (null === $opposingSide) {
            throw new PermissionDeniedException('you do not take part in this fight');
        }

        if ($fight->declaredBy === $confirmingSide->getValue()) {
            throw new PermissionDeniedException('the declaring side cannot confirm its own outcome');
        }

        if (ResultStatus::REPORTING !== $resultOne->getStatus() || ResultStatus::REPORTING !== $resultTwo->getStatus()) {
            throw new ConflictException('this fight is not awaiting a confirmation');
        }

        self::ensureResultOf($fight, $resultOne, $opposingSide->getValue() === $resultOne->getCompetitor()->getValue() ? $opposingSide : $confirmingSide);
        self::ensureResultOf($fight, $resultTwo, $opposingSide->getValue() === $resultTwo->getCompetitor()->getValue() ? $opposingSide : $confirmingSide);
        if ($resultOne->getCompetitor()->getValue() === $resultTwo->getCompetitor()->getValue()) {
            throw new \InvalidArgumentException('both results belong to the same side');
        }

        $winner = null;
        foreach ([[$resultOne, $resultTwo], [$resultTwo, $resultOne]] as [$result, $other]) {
            $status = $result->getReportedStatus() ?? self::outcome($result->getScore(), $other->getScore());
            self::confirmResult($fight, $result, $status);

            if (ResultStatus::WIN === $status) {
                $winner = $result->getCompetitor();
            }
        }

        $fight->setUpdatedAt(new \DateTimeImmutable('now'));

        $fight->recordDomainEvent(new FightSettledEvent($fight->getId(), $winner, $fight->getTournament()));
    }

    /**
     * An administrator settles a fight its sides disagree on: the scores they
     * impose stand, whatever was declared, and the fight is settled as if
     * confirmed. `$resultOne` is the result of the first side, `$resultTwo`
     * of the second.
     */
    public static function arbitrate(
        Fight $fight,
        Result $resultOne,
        Score $scoreOne,
        Result $resultTwo,
        Score $scoreTwo,
    ): void {
        self::ensureResultOf($fight, $resultOne, $fight->getCompetitorOne());
        self::ensureResultOf($fight, $resultTwo, $fight->getCompetitorTwo());

        foreach ([$resultOne, $resultTwo] as $result) {
            if (!\in_array($result->getStatus(), [ResultStatus::PENDING, ResultStatus::REPORTING], true)) {
                throw new ConflictException('this fight is already settled');
            }
        }

        $statusOne = self::outcome($scoreOne->getValue(), $scoreTwo->getValue());
        if (null !== $fight->tournament && ResultStatus::DRAW === $statusOne) {
            throw new ValidationException('a tournament fight cannot end in a draw');
        }

        $winner = null;
        foreach ([[$resultOne, $scoreOne, $statusOne], [$resultTwo, $scoreTwo, self::outcome($scoreTwo->getValue(), $scoreOne->getValue())]] as [$result, $score, $status]) {
            $result->setScore($score->getValue());
            self::confirmResult($fight, $result, $status);

            if (ResultStatus::WIN === $status) {
                $winner = $result->getCompetitor();
            }
        }

        $fight->arbitrated = true;
        $fight->setUpdatedAt(new \DateTimeImmutable('now'));

        $fight->recordDomainEvent(new FightSettledEvent($fight->getId(), $winner, $fight->getTournament()));
    }

    /**
     * An administrator sets aside a declaration in dispute: both sides are
     * back to pending, and one of them declares again.
     */
    public static function reopen(Fight $fight, Result $resultOne, Result $resultTwo): void
    {
        self::ensureResultOf($fight, $resultOne, $fight->getCompetitorOne());
        self::ensureResultOf($fight, $resultTwo, $fight->getCompetitorTwo());

        foreach ([$resultOne, $resultTwo] as $result) {
            if (ResultStatus::PENDING === $result->getStatus()) {
                throw new ConflictException('no outcome has been declared on this fight yet');
            }
            if (ResultStatus::REPORTING !== $result->getStatus()) {
                throw new ConflictException('this fight is already settled');
            }
        }

        foreach ([$resultOne, $resultTwo] as $result) {
            $result->setScore(0);
            $result->setStatus(ResultStatus::PENDING);
            $result->setReportedStatus(null);
            $result->setUpdatedAt(new \DateTimeImmutable('now'));

            $fight->recordDomainEvent(new ResultUpdatedEvent($result->getId()));
        }

        $fight->setDeclaredBy(null);
        $fight->setUpdatedAt(new \DateTimeImmutable('now'));
    }

    private static function outcome(int $score, int $against): ResultStatus
    {
        return match (true) {
            $score > $against => ResultStatus::WIN,
            $score < $against => ResultStatus::LOSS,
            default => ResultStatus::DRAW,
        };
    }

    private static function ensureResultOf(Fight $fight, Result $result, CompetitorId $side): void
    {
        if ($result->getFight()->getValue() !== $fight->id || $result->getCompetitor()->getValue() !== $side->getValue()) {
            throw new \InvalidArgumentException('this result is not the one of that side in this fight');
        }
    }
}
