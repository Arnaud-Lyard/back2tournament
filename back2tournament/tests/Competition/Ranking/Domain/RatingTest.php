<?php

declare(strict_types=1);

namespace App\Tests\Competition\Ranking\Domain;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Entity\RatingChangeId;
use App\Competition\Ranking\Domain\Entity\RatingId;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Event\RatingCreatedEvent;
use App\Competition\Ranking\Domain\Event\RatingUpdatedEvent;
use PHPUnit\Framework\TestCase;

final class RatingTest extends TestCase
{
    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const OTHER_GAME_ID = '12121212-1212-4121-8121-121212121212';
    private const FIGHT_ID = '22222222-2222-4222-8222-222222222222';
    private const ALICE = '33333333-3333-4333-8333-333333333333';
    private const BOB = '44444444-4444-4444-8444-444444444444';
    private const CHANGE_OF_ONE = '55555555-5555-4555-8555-555555555555';
    private const CHANGE_OF_TWO = '66666666-6666-4666-8666-666666666666';

    public function test_a_rating_starts_at_1000_with_no_fight(): void
    {
        $rating = $this->rating(self::ALICE);

        $this->assertSame(Rating::INITIAL, $rating->getValue());
        $this->assertSame(1000, $rating->getValue());
        $this->assertSame([0, 0, 0, 0], [$rating->getFights(), $rating->getWins(), $rating->getDraws(), $rating->getLosses()]);
        $this->assertSame(RankingSubject::PLAYER, $rating->getSubjectType());
        $this->assertSame(self::ALICE, $rating->getSubject());
        $this->assertSame(self::GAME_ID, $rating->getGame()->getValue());
        $this->assertInstanceOf(RatingCreatedEvent::class, $rating->pullDomainEvents()[0]);
    }

    public function test_between_equals_the_winner_takes_16_points_from_the_loser(): void
    {
        $alice = $this->rating(self::ALICE);
        $bob = $this->rating(self::BOB);

        $this->settle($alice, $bob, $alice);

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

        $this->settle($alice, $bob, null);

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
        $this->settle($alice, $bob, $alice);
        $this->settle($alice, $bob, $alice);
        $this->settle($alice, $bob, $bob);

        $this->assertSame(2000, $alice->getValue() + $bob->getValue());
    }

    public function test_each_side_keeps_the_change_the_fight_made(): void
    {
        $alice = $this->rating(self::ALICE);
        $bob = $this->rating(self::BOB);
        $alice->pullDomainEvents();

        [$ofAlice, $ofBob] = $this->settle($alice, $bob, $bob);

        $this->assertSame(self::CHANGE_OF_ONE, $ofAlice->getId()->getValue());
        $this->assertSame($alice->getId()->getValue(), $ofAlice->getRating()->getValue());
        $this->assertSame(self::FIGHT_ID, $ofAlice->getFight()->getValue());
        $this->assertSame([1000, 984, -16], [$ofAlice->getBefore(), $ofAlice->getAfter(), $ofAlice->getPoints()]);
        $this->assertSame([1000, 1016, 16], [$ofBob->getBefore(), $ofBob->getAfter(), $ofBob->getPoints()]);
        $this->assertInstanceOf(RatingUpdatedEvent::class, $alice->pullDomainEvents()[0]);
    }

    public function test_a_rating_does_not_fight_itself(): void
    {
        $alice = $this->rating(self::ALICE);

        $this->expectException(\InvalidArgumentException::class);

        $this->settle($alice, $alice, null);
    }

    public function test_a_player_and_a_clan_are_not_in_the_same_ranking(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settle($this->rating(self::ALICE), $this->rating(self::BOB, RankingSubject::CLAN), null);
    }

    public function test_two_games_are_two_rankings(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settle($this->rating(self::ALICE), $this->rating(self::BOB, gameId: self::OTHER_GAME_ID), null);
    }

    public function test_the_winner_is_one_of_the_two_sides(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settle($this->rating(self::ALICE), $this->rating(self::BOB), $this->rating(self::ALICE));
    }

    private function rating(string $subject, RankingSubject $subjectType = RankingSubject::PLAYER, string $gameId = self::GAME_ID): Rating
    {
        return Rating::start(new RatingId(\sprintf('%s-4777-8777-777777777777', substr($subject, 0, 13))), $subjectType, $subject, new GameId($gameId));
    }

    /**
     * @return array{\App\Competition\Ranking\Domain\Entity\RatingChange, \App\Competition\Ranking\Domain\Entity\RatingChange}
     */
    private function settle(Rating $one, Rating $two, ?Rating $winner): array
    {
        return Rating::settle($one, $two, $winner, new FightId(self::FIGHT_ID), new RatingChangeId(self::CHANGE_OF_ONE), new RatingChangeId(self::CHANGE_OF_TWO));
    }
}
