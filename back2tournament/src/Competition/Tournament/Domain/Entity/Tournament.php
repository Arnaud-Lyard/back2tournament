<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Domain\Entity;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Competition\Tournament\Domain\Enum\TournamentStatus;
use App\Competition\Tournament\Domain\Event\ParticipantRegisteredEvent;
use App\Competition\Tournament\Domain\Event\ParticipantWithdrawnEvent;
use App\Competition\Tournament\Domain\Event\TournamentCancelledEvent;
use App\Competition\Tournament\Domain\Event\TournamentCreatedEvent;
use App\Competition\Tournament\Domain\Event\TournamentFinishedEvent;
use App\Competition\Tournament\Domain\Event\TournamentStartedEvent;
use App\Shared\Aggregate\AggregateRoot;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\ValidationException;

/**
 * A single-elimination tournament of one game, in one format.
 *
 * upcoming (registrations open) → ongoing (bracket drawn) → finished,
 * or cancelled at any point before it is finished.
 */
class Tournament extends AggregateRoot
{
    public const MIN_CAPACITY = 2;

    public const MAX_CAPACITY = 128;

    private string $id;

    private string $name;

    private string $game;

    private int $teamSize;

    private int $capacity;

    private TournamentStatus $status;

    private string $organizer;

    private \DateTimeImmutable $startsAt;

    private ?string $winner = null;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function __construct(TournamentId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): TournamentId
    {
        return new TournamentId($this->id);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGame(): GameId
    {
        return new GameId($this->game);
    }

    public function getTeamSize(): int
    {
        return $this->teamSize;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getStatus(): TournamentStatus
    {
        return $this->status;
    }

    public function getOrganizer(): OrganizerId
    {
        return new OrganizerId($this->organizer);
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getWinner(): ?CompetitorId
    {
        return null === $this->winner ? null : new CompetitorId($this->winner);
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
        TournamentId $tournamentId,
        TournamentName $name,
        GameId $gameId,
        TeamSize $teamSize,
        int $capacity,
        OrganizerId $organizer,
        \DateTimeImmutable $startsAt,
    ): self {
        if ($capacity < self::MIN_CAPACITY || $capacity > self::MAX_CAPACITY) {
            throw new ValidationException(\sprintf(
                'a tournament holds between %d and %d participants',
                self::MIN_CAPACITY,
                self::MAX_CAPACITY,
            ));
        }

        if ($startsAt < new \DateTimeImmutable('now')) {
            throw new ValidationException('a tournament cannot start in the past');
        }

        $tournament = new self($tournamentId);
        $tournament->name = $name->getValue();
        $tournament->game = $gameId->getValue();
        $tournament->teamSize = $teamSize->getValue();
        $tournament->capacity = $capacity;
        $tournament->status = TournamentStatus::UPCOMING;
        $tournament->organizer = $organizer->getValue();
        $tournament->startsAt = $startsAt;
        $tournament->setCreatedAt(new \DateTimeImmutable('now'));
        $tournament->setUpdatedAt(new \DateTimeImmutable('now'));

        $tournament->recordDomainEvent(new TournamentCreatedEvent($tournamentId));

        return $tournament;
    }

    /**
     * @param int $registered how many participants the tournament already counts
     */
    public static function ensureOpenForRegistration(Tournament $tournament, int $registered): void
    {
        if (TournamentStatus::UPCOMING !== $tournament->status) {
            throw new ConflictException('registrations are closed');
        }

        if ($registered >= $tournament->capacity) {
            throw new ConflictException('the tournament is full');
        }
    }

    /**
     * @param int $registered how many participants the tournament already counts
     */
    public static function register(
        Tournament $tournament,
        ParticipantId $participantId,
        CompetitorId $competitorId,
        int $registered,
    ): Participant {
        self::ensureOpenForRegistration($tournament, $registered);

        $participant = new Participant($participantId);
        $participant->setTournament($tournament->getId());
        $participant->setCompetitor($competitorId);
        $participant->setSeed($registered + 1);
        $participant->setCreatedAt(new \DateTimeImmutable('now'));
        $participant->setUpdatedAt(new \DateTimeImmutable('now'));

        $tournament->setUpdatedAt(new \DateTimeImmutable('now'));

        $tournament->recordDomainEvent(new ParticipantRegisteredEvent($participantId));

        return $participant;
    }

    /**
     * The participant leaves before the bracket is drawn; those registered
     * after it move up one seed.
     *
     * @param list<Participant> $registered every participant, the leaving one included
     *
     * @return list<Participant> the participants whose seed changed
     */
    public static function withdraw(Tournament $tournament, Participant $leaving, array $registered): array
    {
        if ($leaving->getTournament()->getValue() !== $tournament->id) {
            throw new ValidationException('this participant is registered in another tournament');
        }

        if (TournamentStatus::UPCOMING !== $tournament->status) {
            throw new ConflictException('the tournament has already started');
        }

        $reseeded = [];
        foreach ($registered as $participant) {
            if ($participant->getSeed() > $leaving->getSeed()) {
                $participant->setSeed($participant->getSeed() - 1);
                $participant->setUpdatedAt(new \DateTimeImmutable('now'));
                $reseeded[] = $participant;
            }
        }

        $tournament->setUpdatedAt(new \DateTimeImmutable('now'));

        $tournament->recordDomainEvent(new ParticipantWithdrawnEvent($leaving->getId()));

        return $reseeded;
    }

    /**
     * Closes registrations and draws the bracket: seed 1 meets the last seed,
     * seed 2 the one before, and so on. When the participants do not fill a
     * power of two, the top seeds get a bye and go straight to round 2.
     *
     * @param list<Participant> $participants
     * @param list<MatchupId>   $matchupIds   one per matchup of the bracket: bracketSize() - 1
     *
     * @return list<Matchup> the whole bracket, round by round, top to bottom
     */
    public static function start(Tournament $tournament, array $participants, array $matchupIds): array
    {
        if (TournamentStatus::UPCOMING !== $tournament->status) {
            throw new ConflictException('the tournament has already started, or is over');
        }

        if (\count($participants) < self::MIN_CAPACITY) {
            throw new ConflictException('a tournament needs at least two participants to start');
        }

        $size = self::bracketSize(\count($participants));
        if (\count($matchupIds) !== $size - 1) {
            throw new \InvalidArgumentException(\sprintf('a bracket of %d holds %d matchups', $size, $size - 1));
        }

        $bySeed = [];
        foreach ($participants as $participant) {
            $bySeed[$participant->getSeed()] = $participant->getCompetitor();
        }

        $bracket = [];
        $round = 1;
        for ($matchups = intdiv($size, 2); $matchups >= 1; $matchups = intdiv($matchups, 2)) {
            for ($position = 0; $position < $matchups; ++$position) {
                $matchup = new Matchup(array_shift($matchupIds));
                $matchup->setTournament($tournament->getId());
                $matchup->setRound($round);
                $matchup->setPosition($position);
                $matchup->setCreatedAt(new \DateTimeImmutable('now'));
                $matchup->setUpdatedAt(new \DateTimeImmutable('now'));
                $bracket[] = $matchup;
            }
            ++$round;
        }

        $order = self::seedingOrder($size);
        foreach ($bracket as $matchup) {
            if (1 !== $matchup->getRound()) {
                break;
            }
            $matchup->setCompetitorOne($bySeed[$order[2 * $matchup->getPosition()]] ?? null);
            $matchup->setCompetitorTwo($bySeed[$order[2 * $matchup->getPosition() + 1]] ?? null);
        }

        $tournament->status = TournamentStatus::ONGOING;
        $tournament->setUpdatedAt(new \DateTimeImmutable('now'));

        $tournament->recordDomainEvent(new TournamentStartedEvent($tournament->getId()));

        // A seed facing nobody moves on at once.
        foreach ($bracket as $matchup) {
            if (1 === $matchup->getRound() && (null === $matchup->getCompetitorOne()) !== (null === $matchup->getCompetitorTwo())) {
                self::advance($tournament, $bracket, $matchup, $matchup->getCompetitorOne() ?? $matchup->getCompetitorTwo());
            }
        }

        return $bracket;
    }

    /**
     * The fight of a matchup is settled: its winner moves on to the next round,
     * or wins the tournament when the matchup was the final.
     *
     * @param list<Matchup> $bracket
     *
     * @return Matchup|null the matchup the winner moves on to, null after the final
     */
    public static function recordWinner(Tournament $tournament, array $bracket, Matchup $decided, CompetitorId $winner): ?Matchup
    {
        if (TournamentStatus::ONGOING !== $tournament->status) {
            throw new ConflictException('the tournament is not under way');
        }

        if (null !== $decided->getWinner()) {
            throw new ConflictException('this matchup is already decided');
        }

        if (!\in_array($winner->getValue(), [$decided->getCompetitorOne()?->getValue(), $decided->getCompetitorTwo()?->getValue()], true)) {
            throw new ValidationException('the winner does not play in this matchup');
        }

        return self::advance($tournament, $bracket, $decided, $winner);
    }

    public static function attachFight(Tournament $tournament, Matchup $matchup, FightId $fightId): Matchup
    {
        if ($matchup->getTournament()->getValue() !== $tournament->id) {
            throw new \InvalidArgumentException('this matchup belongs to another tournament');
        }

        $matchup->setFight($fightId);
        $matchup->setUpdatedAt(new \DateTimeImmutable('now'));

        return $matchup;
    }

    public static function cancel(Tournament $tournament): self
    {
        if (\in_array($tournament->status, [TournamentStatus::FINISHED, TournamentStatus::CANCELLED], true)) {
            throw new ConflictException('this tournament is already over');
        }

        $tournament->status = TournamentStatus::CANCELLED;
        $tournament->setUpdatedAt(new \DateTimeImmutable('now'));

        $tournament->recordDomainEvent(new TournamentCancelledEvent($tournament->getId()));

        return $tournament;
    }

    /**
     * The smallest power of two that seats every participant.
     */
    public static function bracketSize(int $participants): int
    {
        $size = 2;
        while ($size < $participants) {
            $size *= 2;
        }

        return $size;
    }

    /**
     * The matchups whose two sides are known and whose fight is still to open.
     *
     * @param list<Matchup> $bracket
     *
     * @return list<Matchup>
     */
    public static function readyForFight(array $bracket): array
    {
        return array_values(array_filter(
            $bracket,
            static fn (Matchup $matchup): bool => null !== $matchup->getCompetitorOne()
                && null !== $matchup->getCompetitorTwo()
                && null === $matchup->getFight()
                && null === $matchup->getWinner(),
        ));
    }

    /**
     * Seeds in bracket order, top to bottom: for 8, [1, 8, 4, 5, 2, 7, 3, 6],
     * so that the two best seeds can only meet in the final.
     *
     * @return list<int>
     */
    private static function seedingOrder(int $size): array
    {
        $order = [1];
        while (\count($order) < $size) {
            $sum = 2 * \count($order) + 1;
            $next = [];
            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $sum - $seed;
            }
            $order = $next;
        }

        return $order;
    }

    /**
     * @param list<Matchup> $bracket
     */
    private static function advance(Tournament $tournament, array $bracket, Matchup $decided, CompetitorId $winner): ?Matchup
    {
        $decided->setWinner($winner);
        $decided->setUpdatedAt(new \DateTimeImmutable('now'));

        foreach ($bracket as $next) {
            if ($next->getRound() === $decided->getRound() + 1 && $next->getPosition() === intdiv($decided->getPosition(), 2)) {
                if (0 === $decided->getPosition() % 2) {
                    $next->setCompetitorOne($winner);
                } else {
                    $next->setCompetitorTwo($winner);
                }
                $next->setUpdatedAt(new \DateTimeImmutable('now'));

                return $next;
            }
        }

        // No next round: that was the final.
        $tournament->winner = $winner->getValue();
        $tournament->status = TournamentStatus::FINISHED;
        $tournament->setUpdatedAt(new \DateTimeImmutable('now'));

        $tournament->recordDomainEvent(new TournamentFinishedEvent($tournament->getId()));

        return null;
    }
}
