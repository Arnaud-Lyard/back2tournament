<?php

declare(strict_types=1);

namespace App\Tests\Competition\Ranking\Domain;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Entity\RatingChange;
use App\Competition\Ranking\Domain\Entity\RatingChangeId;
use App\Competition\Ranking\Domain\Entity\RatingId;
use App\Competition\Ranking\Domain\Enum\FightOutcome;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Event\RatingCreatedEvent;
use App\Competition\Ranking\Domain\Event\RatingUpdatedEvent;
use App\Shared\ValueObject\TeamSizeValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RatingTest extends TestCase
{
    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const OTHER_GAME_ID = '12121212-1212-4121-8121-121212121212';
    private const FIGHT_ID = '22222222-2222-4222-8222-222222222222';
    private const ALICE = '33333333-3333-4333-8333-333333333333';
    private const BOB = '44444444-4444-4444-8444-444444444444';
    private const CAROL = '45454545-4545-4454-8454-454545454545';
    private const DAVE = '46464646-4646-4464-8464-464646464646';
    private const CHANGE_OF_ONE = '55555555-5555-4555-8555-555555555555';
    private const CHANGE_OF_TWO = '66666666-6666-4666-8666-666666666666';

    public function test_a_rating_starts_at_1000_with_no_fight_in_its_format(): void
    {
        $rating = $this->rating(self::ALICE, teamSize: 2);

        $this->assertSame(Rating::INITIAL, $rating->getValue());
        $this->assertSame(1000, $rating->getValue());
        $this->assertSame([0, 0, 0, 0], [$rating->getFights(), $rating->getWins(), $rating->getDraws(), $rating->getLosses()]);
        $this->assertSame(RankingSubject::PLAYER, $rating->getSubjectType());
        $this->assertSame(self::ALICE, $rating->getSubject());
        $this->assertSame(self::GAME_ID, $rating->getGame()->getValue());
        $this->assertSame(2, $rating->getTeamSize());
        $this->assertInstanceOf(RatingCreatedEvent::class, $rating->pullDomainEvents()[0]);
    }

    public function test_between_equals_the_winner_takes_16_points_from_the_loser(): void
    {
        $alice = $this->rating(self::ALICE);
        $bob = $this->rating(self::BOB);

        $this->settle([$alice], [$bob], FightOutcome::SIDE_ONE_WON);

        $this->assertSame(1016, $alice->getValue());
        $this->assertSame(984, $bob->getValue());
        $this->assertSame([1, 1, 0, 0], [$alice->getFights(), $alice->getWins(), $alice->getDraws(), $alice->getLosses()]);
        $this->assertSame([1, 0, 0, 1], [$bob->getFights(), $bob->getWins(), $bob->getDraws(), $bob->getLosses()]);
    }

    public function test_an_unexpected_win_is_worth_more_than_an_expected_one(): void
    {
        // 1200 against 1000: the favourite was expected to score 0.76.
        $this->assertSame(8, Rating::points(1200, 1000, 1.0));
        $this->assertSame(24, Rating::points(1000, 1200, 1.0));
        $this->assertSame(-24, Rating::points(1200, 1000, 0.0));
        $this->assertSame(-8, Rating::points(1000, 1200, 0.0));
    }

    public function test_a_draw_between_equals_moves_nothing_and_counts_as_a_draw(): void
    {
        $alice = $this->rating(self::ALICE);
        $bob = $this->rating(self::BOB);

        $this->settle([$alice], [$bob], FightOutcome::DRAW);

        $this->assertSame([1000, 1000], [$alice->getValue(), $bob->getValue()]);
        $this->assertSame([1, 0, 1, 0], [$alice->getFights(), $alice->getWins(), $alice->getDraws(), $alice->getLosses()]);
        $this->assertSame([1, 0, 1, 0], [$bob->getFights(), $bob->getWins(), $bob->getDraws(), $bob->getLosses()]);
    }

    public function test_a_draw_lifts_the_lower_rating(): void
    {
        $this->assertSame(8, Rating::points(1000, 1200, 0.5));
        $this->assertSame(-8, Rating::points(1200, 1000, 0.5));
    }

    public function test_what_one_side_wins_the_other_loses(): void
    {
        $alice = $this->rating(self::ALICE);
        $bob = $this->rating(self::BOB);
        $this->settle([$alice], [$bob], FightOutcome::SIDE_ONE_WON);
        $this->settle([$alice], [$bob], FightOutcome::SIDE_ONE_WON);
        $this->settle([$alice], [$bob], FightOutcome::SIDE_TWO_WON);

        $this->assertSame(2000, $alice->getValue() + $bob->getValue());
    }

    public function test_every_player_of_a_side_moves_by_what_the_fight_was_worth_to_the_side(): void
    {
        // 1031 and 1016 average 1023.5, 984 and 969 average 976.5: the
        // favourites were expected to score 0.57, and win 14 each.
        $alice = $this->record(self::ALICE, wins: 2);
        $bob = $this->record(self::BOB, wins: 1);
        $carol = $this->record(self::CAROL, losses: 1);
        $dave = $this->record(self::DAVE, losses: 2);

        $changes = Rating::settle([$alice, $bob], [$carol, $dave], FightOutcome::SIDE_ONE_WON, new FightId(self::FIGHT_ID), $this->changeIds(4));

        $this->assertSame(14, Rating::points(1023.5, 976.5, 1.0));
        $this->assertSame([14, 14, -14, -14], array_map(static fn (RatingChange $change): int => $change->getPoints(), $changes));
        $this->assertSame([1045, 1030, 970, 955], [$alice->getValue(), $bob->getValue(), $carol->getValue(), $dave->getValue()]);
        $this->assertSame([3, 2, 0, 0], [$alice->getWins(), $bob->getWins(), $carol->getWins(), $dave->getWins()]);
        $this->assertSame([0, 0, 2, 3], [$alice->getLosses(), $bob->getLosses(), $carol->getLosses(), $dave->getLosses()]);
    }

    public function test_each_rating_keeps_the_change_the_fight_made(): void
    {
        $alice = $this->rating(self::ALICE);
        $bob = $this->rating(self::BOB);
        $alice->pullDomainEvents();

        [$ofAlice, $ofBob] = Rating::settle(
            [$alice],
            [$bob],
            FightOutcome::SIDE_TWO_WON,
            new FightId(self::FIGHT_ID),
            [new RatingChangeId(self::CHANGE_OF_ONE), new RatingChangeId(self::CHANGE_OF_TWO)],
        );

        $this->assertSame(self::CHANGE_OF_ONE, $ofAlice->getId()->getValue());
        $this->assertSame($alice->getId()->getValue(), $ofAlice->getRating()->getValue());
        $this->assertSame(self::FIGHT_ID, $ofAlice->getFight()->getValue());
        $this->assertSame([1000, 984, -16], [$ofAlice->getBefore(), $ofAlice->getAfter(), $ofAlice->getPoints()]);
        $this->assertSame(self::CHANGE_OF_TWO, $ofBob->getId()->getValue());
        $this->assertSame([1000, 1016, 16], [$ofBob->getBefore(), $ofBob->getAfter(), $ofBob->getPoints()]);
        $this->assertInstanceOf(RatingUpdatedEvent::class, $alice->pullDomainEvents()[0]);
    }

    public function test_a_rating_does_not_stand_twice_in_one_fight(): void
    {
        $alice = $this->rating(self::ALICE);

        $this->expectException(\InvalidArgumentException::class);

        $this->settle([$alice], [$alice], FightOutcome::DRAW);
    }

    public function test_a_side_has_at_least_one_rating(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settle([$this->rating(self::ALICE)], [], FightOutcome::DRAW);
    }

    public function test_every_rating_moves_with_a_change_of_its_own(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Rating::settle([$this->rating(self::ALICE)], [$this->rating(self::BOB)], FightOutcome::DRAW, new FightId(self::FIGHT_ID), $this->changeIds(1));
    }

    public function test_a_player_and_a_clan_are_not_in_the_same_ranking(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settle([$this->rating(self::ALICE)], [$this->rating(self::BOB, RankingSubject::CLAN)], FightOutcome::DRAW);
    }

    public function test_two_games_are_two_rankings(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settle([$this->rating(self::ALICE)], [$this->rating(self::BOB, gameId: self::OTHER_GAME_ID)], FightOutcome::DRAW);
    }

    public function test_two_formats_are_two_rankings(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settle([$this->rating(self::ALICE)], [$this->rating(self::BOB, teamSize: 2)], FightOutcome::DRAW);
    }

    private function rating(string $subject, RankingSubject $subjectType = RankingSubject::PLAYER, string $gameId = self::GAME_ID, int $teamSize = 1): Rating
    {
        return Rating::start(
            new RatingId(\sprintf('%s-4777-8777-777777777777', substr($subject, 0, 13))),
            $subjectType,
            $subject,
            new GameId($gameId),
            new TeamSizeValueObject($teamSize),
        );
    }

    /**
     * A 2v2 rating made by $wins wins, then $losses losses, each against a
     * newcomer at 1000: one win makes 1016, two 1031; one loss 984, two 969.
     */
    private function record(string $subject, int $wins = 0, int $losses = 0): Rating
    {
        $rating = $this->rating($subject, teamSize: 2);

        for ($fight = 0; $fight < $wins + $losses; ++$fight) {
            $newcomer = Rating::start(new RatingId(Uuid::v4()->toString()), RankingSubject::PLAYER, Uuid::v4()->toString(), new GameId(self::GAME_ID), new TeamSizeValueObject(2));
            $this->settle([$rating], [$newcomer], $fight < $wins ? FightOutcome::SIDE_ONE_WON : FightOutcome::SIDE_TWO_WON);
        }

        return $rating;
    }

    /**
     * @param list<Rating> $sideOne
     * @param list<Rating> $sideTwo
     *
     * @return list<RatingChange>
     */
    private function settle(array $sideOne, array $sideTwo, FightOutcome $outcome): array
    {
        return Rating::settle($sideOne, $sideTwo, $outcome, new FightId(self::FIGHT_ID), $this->changeIds(\count($sideOne) + \count($sideTwo)));
    }

    /**
     * @return list<RatingChangeId>
     */
    private function changeIds(int $count): array
    {
        return array_map(static fn (): RatingChangeId => new RatingChangeId(Uuid::v4()->toString()), range(1, $count));
    }
}
