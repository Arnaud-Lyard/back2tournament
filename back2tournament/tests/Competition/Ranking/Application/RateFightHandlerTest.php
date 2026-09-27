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
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Ranking\Application\Model\RateFightCommand;
use App\Competition\Ranking\Application\Service\RateFightHandler;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Entity\RatingChange;
use App\Competition\Ranking\Domain\Entity\RatingChangeId;
use App\Competition\Ranking\Domain\Entity\RatingId;
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

    /** @var list<object> */
    private array $saved = [];

    public function test_a_settled_duel_starts_both_ratings_and_moves_them(): void
    {
        $this->handler($this->settled(ResultStatus::WIN))(new RateFightCommand(self::FIGHT_ID));

        [$alice, $bob] = $this->savedRatings();
        $this->assertSame([RankingSubject::PLAYER, self::ALICE, 1016, 1], [$alice->getSubjectType(), $alice->getSubject(), $alice->getValue(), $alice->getWins()]);
        $this->assertSame([RankingSubject::PLAYER, self::BOB, 984, 1], [$bob->getSubjectType(), $bob->getSubject(), $bob->getValue(), $bob->getLosses()]);
        $this->assertSame(self::GAME_ID, $alice->getGame()->getValue());
        $this->assertCount(2, array_filter($this->saved, static fn (object $saved): bool => $saved instanceof RatingChange));
    }

    public function test_a_team_fight_moves_the_ratings_of_the_two_clans(): void
    {
        $this->handler($this->settled(ResultStatus::LOSS), sides: [
            self::ONE => ['type' => 'clan', 'id' => self::ALICE],
            self::TWO => ['type' => 'clan', 'id' => self::BOB],
        ])(new RateFightCommand(self::FIGHT_ID));

        [$alice, $bob] = $this->savedRatings();
        $this->assertSame([RankingSubject::CLAN, 984], [$alice->getSubjectType(), $alice->getValue()]);
        $this->assertSame([RankingSubject::CLAN, 1016], [$bob->getSubjectType(), $bob->getValue()]);
    }

    public function test_a_draw_counts_for_both(): void
    {
        $this->handler($this->settled(ResultStatus::DRAW))(new RateFightCommand(self::FIGHT_ID));

        [$alice, $bob] = $this->savedRatings();
        $this->assertSame([1000, 1], [$alice->getValue(), $alice->getDraws()]);
        $this->assertSame([1000, 1], [$bob->getValue(), $bob->getDraws()]);
    }

    public function test_a_rating_already_started_moves_on_from_where_it_stands(): void
    {
        $alice = Rating::start(new RatingId(Uuid::v4()->toString()), RankingSubject::PLAYER, self::ALICE, new GameId(self::GAME_ID));
        $bob = Rating::start(new RatingId(Uuid::v4()->toString()), RankingSubject::PLAYER, self::BOB, new GameId(self::GAME_ID));
        Rating::settle($alice, $bob, $alice, new FightId(Uuid::v4()->toString()), new RatingChangeId(Uuid::v4()->toString()), new RatingChangeId(Uuid::v4()->toString()));

        $this->handler($this->settled(ResultStatus::WIN), ratings: [$alice, $bob])(new RateFightCommand(self::FIGHT_ID));

        // 1016 against 984: the favourite wins 15.
        $this->assertSame([$alice, $bob], $this->savedRatings());
        $this->assertSame([1031, 2, 2], [$alice->getValue(), $alice->getFights(), $alice->getWins()]);
        $this->assertSame([969, 2, 2], [$bob->getValue(), $bob->getFights(), $bob->getLosses()]);
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

    public function test_two_teams_of_one_clan_leave_its_rating_as_it_is(): void
    {
        $this->handler($this->settled(ResultStatus::WIN), sides: [
            self::ONE => ['type' => 'clan', 'id' => self::ALICE],
            self::TWO => ['type' => 'clan', 'id' => self::ALICE],
        ])(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame([], $this->saved);
    }

    public function test_a_side_that_ranks_nowhere_moves_nothing(): void
    {
        $this->handler($this->settled(ResultStatus::WIN), sides: [
            self::ONE => ['type' => 'player', 'id' => self::ALICE],
        ])(new RateFightCommand(self::FIGHT_ID));

        $this->assertSame([], $this->saved);
    }

    public function test_an_unknown_fight_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $this->handler([null, []])(new RateFightCommand(self::FIGHT_ID));
    }

    /**
     * The fight between ONE and TWO, and its two results, ONE's being $outcomeOfOne.
     *
     * @return array{?Fight, list<Result>}
     */
    private function settled(ResultStatus $outcomeOfOne): array
    {
        $fight = Fight::create(new FightId(self::FIGHT_ID), new CompetitorId(self::ONE), new CompetitorId(self::TWO), new GameId(self::GAME_ID), new TeamSizeValueObject(1));
        $one = Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), new CompetitorId(self::ONE));
        $two = Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), new CompetitorId(self::TWO));

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
     * @param array{?Fight, list<Result>}                           $fight
     * @param array<string, array{type: 'player'|'clan', id: string}>|null $sides
     * @param list<Rating>                                          $ratings
     * @param list<RatingChange>                                    $changes
     */
    private function handler(
        array $fight,
        ?array $sides = null,
        array $ratings = [],
        array $changes = [],
        ?EventDispatcherInterface $eventDispatcher = null,
    ): RateFightHandler {
        [$theFight, $results] = $fight;

        $competitorRegistryProvider = $this->createStub(CompetitorRegistryProviderInterface::class);
        $competitorRegistryProvider->method('rankedAs')->willReturn($sides ?? [
            self::ONE => ['type' => 'player', 'id' => self::ALICE],
            self::TWO => ['type' => 'player', 'id' => self::BOB],
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

    /**
     * @return list<Rating>
     */
    private function savedRatings(): array
    {
        return array_values(array_filter($this->saved, static fn (object $saved): bool => $saved instanceof Rating));
    }
}
