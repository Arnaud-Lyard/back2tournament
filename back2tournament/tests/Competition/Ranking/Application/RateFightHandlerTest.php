<?php

declare(strict_types=1);

namespace App\Tests\Competition\Ranking\Application;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Ranking\Application\Model\RateFightCommand;
use App\Competition\Ranking\Application\Service\RateFightHandler;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Entity\RatingChange;
use App\Competition\Ranking\Domain\Entity\RatingChangeId;
use App\Competition\Ranking\Domain\Entity\RatingId;
use App\Competition\Ranking\Domain\Enum\FightOutcome;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Event\RatingUpdatedEvent;
use App\Competition\Ranking\Domain\Repository\RatingChangeRepositoryInterface;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\TeamSizeValueObject;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class RateFightHandlerTest extends TestCase
{
    use RepositoryStubs;

    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const FIGHT_ID = '22222222-2222-4222-8222-222222222222';
    private const ONE = '33333333-3333-4333-8333-333333333333';
    private const TWO = '44444444-4444-4444-8444-444444444444';
    private const ALICE = '55555555-5555-4555-8555-555555555555';
    private const BOB = '66666666-6666-4666-8666-666666666666';
    private const CAROL = '77777777-7777-4777-8777-777777777777';
    private const DAVE = '88888888-8888-4888-8888-888888888888';
    private const CLAN_A = '99999999-9999-4999-8999-999999999999';
    private const CLAN_B = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    /** @var list<object> */
    private array $saved = [];

    public function test_a_settled_duel_starts_both_player_ratings_in_1v1_and_moves_them(): void
    {
        $this->handler($this->settled(ResultStatus::WIN))(new RateFightCommand(self::FIGHT_ID));

        [$alice, $bob] = $this->savedRatings();
        $this->assertSame([RankingSubject::PLAYER, self::ALICE, 1, 1016, 1], [$alice->getSubjectType(), $alice->getSubject(), $alice->getTeamSize(), $alice->getValue(), $alice->getWins()]);
        $this->assertSame([RankingSubject::PLAYER, self::BOB, 1, 984, 1], [$bob->getSubjectType(), $bob->getSubject(), $bob->getTeamSize(), $bob->getValue(), $bob->getLosses()]);
        $this->assertSame(self::GAME_ID, $alice->getGame()->getValue());
        $this->assertCount(2, $this->savedChanges());
    }

    public function test_a_duel_between_members_of_two_clans_also_ranks_the_clans_in_1v1(): void
    {
        $this->handler($this->settled(ResultStatus::WIN, clanOne: self::CLAN_A, clanTwo: self::CLAN_B))(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame(
            [
                ['player', self::ALICE, 1, 1016],
                ['player', self::BOB, 1, 984],
                ['clan', self::CLAN_A, 1, 1016],
                ['clan', self::CLAN_B, 1, 984],
            ],
            $this->standings(),
        );
        $this->assertCount(4, $this->savedChanges());
    }

    public function test_a_team_fight_moves_every_player_of_both_lineups_and_the_two_clans_in_its_format(): void
    {
        $this->handler($this->settled(ResultStatus::LOSS, teamSize: 2, clanOne: self::CLAN_A, clanTwo: self::CLAN_B), lineups: [
            self::ONE => ['players' => [self::ALICE, self::CAROL], 'clan' => self::CLAN_A],
            self::TWO => ['players' => [self::BOB, self::DAVE], 'clan' => self::CLAN_B],
        ])(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame(
            [
                ['player', self::ALICE, 2, 984],
                ['player', self::CAROL, 2, 984],
                ['player', self::BOB, 2, 1016],
                ['player', self::DAVE, 2, 1016],
                ['clan', self::CLAN_A, 2, 984],
                ['clan', self::CLAN_B, 2, 1016],
            ],
            $this->standings(),
        );
        $this->assertCount(6, $this->savedChanges());
    }

    public function test_two_sides_of_one_clan_rank_their_players_and_leave_the_clan_as_it_is(): void
    {
        $this->handler($this->settled(ResultStatus::WIN, clanOne: self::CLAN_A, clanTwo: self::CLAN_A))(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame([['player', self::ALICE, 1, 1016], ['player', self::BOB, 1, 984]], $this->standings());
    }

    public function test_a_player_in_no_clan_ranks_alone(): void
    {
        $this->handler($this->settled(ResultStatus::WIN, clanOne: self::CLAN_A))(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame([['player', self::ALICE, 1, 1016], ['player', self::BOB, 1, 984]], $this->standings());
    }

    public function test_a_side_counts_for_the_clan_it_played_for_when_the_fight_opened(): void
    {
        // Alice has left CLAN_A for CLAN_B since: the duel stays CLAN_A's.
        $this->handler($this->settled(ResultStatus::WIN, clanOne: self::CLAN_A, clanTwo: self::CLAN_B), lineups: [
            self::ONE => ['players' => [self::ALICE], 'clan' => self::CLAN_B],
            self::TWO => ['players' => [self::BOB], 'clan' => self::CLAN_B],
        ])(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame([['clan', self::CLAN_A, 1, 1016], ['clan', self::CLAN_B, 1, 984]], \array_slice($this->standings(), 2));
    }

    public function test_a_draw_counts_for_both(): void
    {
        $this->handler($this->settled(ResultStatus::DRAW))(new RateFightCommand(self::FIGHT_ID));

        [$alice, $bob] = $this->savedRatings();
        $this->assertSame([1000, 1], [$alice->getValue(), $alice->getDraws()]);
        $this->assertSame([1000, 1], [$bob->getValue(), $bob->getDraws()]);
    }

    public function test_a_rating_already_started_in_the_format_moves_on_from_where_it_stands(): void
    {
        $alice = $this->ratingOf(self::ALICE, 1);
        $bob = $this->ratingOf(self::BOB, 1);
        Rating::settle([$alice], [$bob], FightOutcome::SIDE_ONE_WON, new FightId(Uuid::v4()->toString()), $this->changeIds(2));
        // Alice's 2v2 rating has nothing to do with her duels.
        $aliceIn2v2 = $this->ratingOf(self::ALICE, 2);

        $this->handler($this->settled(ResultStatus::WIN), ratings: [$aliceIn2v2, $alice, $bob])(new RateFightCommand(self::FIGHT_ID));

        // 1016 against 984: the favourite wins 15.
        $this->assertSame([$alice, $bob], $this->savedRatings());
        $this->assertSame([1031, 2, 2], [$alice->getValue(), $alice->getFights(), $alice->getWins()]);
        $this->assertSame([969, 2, 2], [$bob->getValue(), $bob->getFights(), $bob->getLosses()]);
        $this->assertSame([1000, 0], [$aliceIn2v2->getValue(), $aliceIn2v2->getFights()]);
    }

    public function test_the_moves_are_announced(): void
    {
        $announced = [];
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(static function (object $event) use (&$announced): object {
            $announced[] = $event::class;

            return $event;
        });

        $this->handler($this->settled(ResultStatus::WIN), eventDispatcher: $eventDispatcher)(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame(2, array_count_values($announced)[RatingUpdatedEvent::class] ?? 0);
    }

    public function test_a_fight_already_counted_is_not_counted_again(): void
    {
        $counted = new RatingChange(new RatingChangeId(Uuid::v4()->toString()), new RatingId(Uuid::v4()->toString()), new FightId(self::FIGHT_ID), 1000, 1016);

        $this->handler($this->settled(ResultStatus::WIN), changes: [$counted])(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame([], $this->saved);
    }

    public function test_a_fight_not_settled_yet_moves_nothing(): void
    {
        $this->handler($this->settled(ResultStatus::REPORTING))(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame([], $this->saved);
    }

    public function test_a_side_nobody_knows_moves_nothing(): void
    {
        $this->handler($this->settled(ResultStatus::WIN, clanOne: self::CLAN_A, clanTwo: self::CLAN_B), lineups: [
            self::ONE => ['players' => [self::ALICE], 'clan' => self::CLAN_A],
        ])(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame([], $this->saved);
    }

    public function test_a_player_on_both_sides_leaves_the_players_as_they_are(): void
    {
        // Fights refuse it; were one let through, only the clans would move.
        $this->handler($this->settled(ResultStatus::WIN, teamSize: 2, clanOne: self::CLAN_A, clanTwo: self::CLAN_B), lineups: [
            self::ONE => ['players' => [self::ALICE, self::CAROL], 'clan' => self::CLAN_A],
            self::TWO => ['players' => [self::ALICE, self::DAVE], 'clan' => self::CLAN_B],
        ])(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame([['clan', self::CLAN_A, 2, 1016], ['clan', self::CLAN_B, 2, 984]], $this->standings());
    }

    public function test_an_unknown_fight_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $this->handler([null, []])(new RateFightCommand(self::FIGHT_ID));
    }

    /**
     * The fight between ONE and TWO in a format, and its two results, ONE's
     * being $outcomeOfOne; each result keeps the clan its side played for.
     *
     * @return array{?Fight, list<Result>}
     */
    private function settled(ResultStatus $outcomeOfOne, int $teamSize = 1, ?string $clanOne = null, ?string $clanTwo = null): array
    {
        $fight = Fight::create(new FightId(self::FIGHT_ID), new CompetitorId(self::ONE), new CompetitorId(self::TWO), new GameId(self::GAME_ID), new TeamSizeValueObject($teamSize));
        $one = Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), new CompetitorId(self::ONE), null === $clanOne ? null : new ClanId($clanOne));
        $two = Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), new CompetitorId(self::TWO), null === $clanTwo ? null : new ClanId($clanTwo));

        $outcomeOfTwo = match ($outcomeOfOne) {
            ResultStatus::WIN => ResultStatus::LOSS,
            ResultStatus::LOSS => ResultStatus::WIN,
            default => $outcomeOfOne,
        };
        $one->setStatus($outcomeOfOne);
        $two->setStatus($outcomeOfTwo);

        return [$fight, [$one, $two]];
    }

    /**
     * @param array{?Fight, list<Result>}                                    $fight
     * @param array<string, array{players: list<string>, clan: ?string}>|null $lineups two player profiles in no clan by default
     * @param list<Rating>                                                   $ratings
     * @param list<RatingChange>                                             $changes
     */
    private function handler(
        array $fight,
        ?array $lineups = null,
        array $ratings = [],
        array $changes = [],
        ?EventDispatcherInterface $eventDispatcher = null,
    ): RateFightHandler {
        [$theFight, $results] = $fight;

        $competitorRegistryProvider = $this->createStub(CompetitorRegistryProviderInterface::class);
        $competitorRegistryProvider->method('lineups')->willReturn($lineups ?? [
            self::ONE => ['players' => [self::ALICE], 'clan' => null],
            self::TWO => ['players' => [self::BOB], 'clan' => null],
        ]);

        $ratingRepository = $this->repositoryStub(RatingRepositoryInterface::class, $ratings);
        $ratingRepository->method('save')->willReturnCallback(function (Rating $rating): void {
            $this->saved[] = $rating;
        });
        $ratingChangeRepository = $this->repositoryStub(RatingChangeRepositoryInterface::class, $changes);
        $ratingChangeRepository->method('save')->willReturnCallback(function (RatingChange $change): void {
            $this->saved[] = $change;
        });

        return new RateFightHandler(
            $this->repositoryStub(FightRepositoryInterface::class, null === $theFight ? [] : [$theFight]),
            $this->repositoryStub(ResultRepositoryInterface::class, $results),
            $competitorRegistryProvider,
            $ratingRepository,
            $ratingChangeRepository,
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
        );
    }

    private function ratingOf(string $player, int $teamSize): Rating
    {
        return Rating::start(new RatingId(Uuid::v4()->toString()), RankingSubject::PLAYER, $player, new GameId(self::GAME_ID), new TeamSizeValueObject($teamSize));
    }

    /**
     * @return list<RatingChangeId>
     */
    private function changeIds(int $count): array
    {
        return array_map(static fn (): RatingChangeId => new RatingChangeId(Uuid::v4()->toString()), range(1, $count));
    }

    /**
     * @return list<Rating>
     */
    private function savedRatings(): array
    {
        return array_values(array_filter($this->saved, static fn (object $saved): bool => $saved instanceof Rating));
    }

    /**
     * @return list<RatingChange>
     */
    private function savedChanges(): array
    {
        return array_values(array_filter($this->saved, static fn (object $saved): bool => $saved instanceof RatingChange));
    }

    /**
     * Each saved rating as [subject type, subject, format, value], in the order it was saved.
     *
     * @return list<array{string, string, int, int}>
     */
    private function standings(): array
    {
        return array_map(
            static fn (Rating $rating): array => [$rating->getSubjectType()->value, $rating->getSubject(), $rating->getTeamSize(), $rating->getValue()],
            $this->savedRatings(),
        );
    }
}
