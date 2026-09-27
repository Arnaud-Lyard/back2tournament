<?php

declare(strict_types=1);

namespace App\Tests\Competition\Ranking\Application;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Ranking\Application\Model\FindRankingQuery;
use App\Competition\Ranking\Application\Service\FindRankingHandler;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Entity\RatingChangeId;
use App\Competition\Ranking\Domain\Entity\RatingId;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use App\Competition\Shared\Domain\Provider\ClanTagProvider;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class FindRankingHandlerTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const ALICE = '22222222-2222-4222-8222-222222222222';
    private const BOB = '33333333-3333-4333-8333-333333333333';
    private const CAROL = '44444444-4444-4444-8444-444444444444';
    private const DAVE = '55555555-5555-4555-8555-555555555555';
    private const USER_ID = '66666666-6666-4666-8666-666666666666';
    private const CLAN_ID = '77777777-7777-4777-8777-777777777777';

    public function test_the_ranking_lists_each_player_with_its_rating_its_record_and_its_rank(): void
    {
        $alice = $this->rating(self::ALICE, wins: 2);
        $bob = $this->rating(self::BOB, losses: 2);

        $page = $this->read($this->handler([$alice, $bob])(FindRankingQuery::ofPlayers(self::GAME_ID, 1, 20)));

        $this->assertSame(
            [
                'rank' => 1,
                'rating' => 1031,
                'fights' => 2,
                'wins' => 2,
                'draws' => 0,
                'losses' => 0,
                'subject' => ['type' => 'player', 'id' => ['value' => self::ALICE], 'name' => 'Alice#0001', 'tag' => 'B2T'],
            ],
            $page['items'][0],
        );
        // Alice leads a clan, Bob is in none.
        $this->assertSame([2, 'Bob#0002', null], [$page['items'][1]['rank'], $page['items'][1]['subject']['name'], $page['items'][1]['subject']['tag']]);
        $this->assertSame([2, 1, 20, 1], [$page['total'], $page['page'], $page['limit'], $page['pages']]);
    }

    public function test_equal_ratings_share_a_rank_and_the_next_one_down_keeps_its_place(): void
    {
        $page = $this->read($this->handler([
            $this->rating(self::ALICE, wins: 2),
            $this->rating(self::BOB, wins: 1),
            $this->rating(self::CAROL, wins: 1),
            $this->rating(self::DAVE, losses: 1),
        ])(FindRankingQuery::ofPlayers(self::GAME_ID, 1, 20)));

        $this->assertSame([1, 2, 2, 4], array_column($page['items'], 'rank'));
    }

    public function test_a_later_page_ranks_from_where_the_ranking_stands(): void
    {
        // The second page of two entries: its first one ties with the last of page one.
        $page = $this->read($this->handler(
            [$this->rating(self::CAROL, wins: 1), $this->rating(self::DAVE, losses: 1)],
            above: [1016 => 1, 984 => 3],
            total: 4,
        )(FindRankingQuery::ofPlayers(self::GAME_ID, 2, 2)));

        $this->assertSame([2, 4], array_column($page['items'], 'rank'));
        $this->assertSame([4, 2, 2, 2], [$page['total'], $page['page'], $page['limit'], $page['pages']]);
    }

    public function test_a_clan_is_named_with_its_tag(): void
    {
        $page = $this->read($this->handler([$this->rating(self::CLAN_ID, RankingSubject::CLAN, wins: 1)])(FindRankingQuery::ofClans(self::GAME_ID, 1, 20)));

        $this->assertSame(
            ['type' => 'clan', 'id' => ['value' => self::CLAN_ID], 'name' => 'Back to Tournament', 'tag' => 'B2T'],
            $page['items'][0]['subject'],
        );
    }

    public function test_nobody_ranked_yet_reads_as_an_empty_page(): void
    {
        $page = $this->read($this->handler([])(FindRankingQuery::ofClans(self::GAME_ID, 1, 20)));

        $this->assertSame([[], 0, 0], [$page['items'], $page['total'], $page['pages']]);
    }

    public function test_an_unknown_game_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $this->handler([], game: false)(FindRankingQuery::ofPlayers(self::GAME_ID, 1, 20));
    }

    public function test_a_game_id_that_is_no_uuid_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler([])(FindRankingQuery::ofPlayers('not-a-uuid', 1, 20));
    }

    /**
     * @param list<Rating>      $ratings the page, highest first
     * @param array<int, int>|null $above   how many rate higher than each value; computed from $ratings when null
     */
    private function handler(array $ratings, ?array $above = null, ?int $total = null, bool $game = true): FindRankingHandler
    {
        $ratingRepository = $this->createStub(RatingRepositoryInterface::class);
        $ratingRepository->method('findRanking')->willReturn($ratings);
        $ratingRepository->method('countRanking')->willReturn($total ?? \count($ratings));
        $ratingRepository->method('countAbove')->willReturnCallback(
            static fn (RankingSubject $subjectType, string $gameId, int $value): int => $above[$value]
                ?? \count(array_filter($ratings, static fn (Rating $rating): bool => $rating->getValue() > $value))
        );

        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::ALICE);

        return new FindRankingHandler(
            $ratingRepository,
            $this->repositoryStub(GameRepositoryInterface::class, $game ? [self::aGame(self::GAME_ID)] : []),
            $this->repositoryStub(PlayerRepositoryInterface::class, [
                self::aPlayer(self::ALICE, self::USER_ID, self::GAME_ID, 'Alice#0001'),
                self::aPlayer(self::BOB, self::USER_ID, self::GAME_ID, 'Bob#0002'),
                self::aPlayer(self::CAROL, self::USER_ID, self::GAME_ID, 'Carol#0003'),
                self::aPlayer(self::DAVE, self::USER_ID, self::GAME_ID, 'Dave#0004'),
            ]),
            $this->repositoryStub(ClanRepositoryInterface::class, [$clan]),
            new ClanTagProvider(
                $this->repositoryStub(ClanMemberRepositoryInterface::class, [self::leadership($clan)]),
                $this->repositoryStub(ClanRepositoryInterface::class, [$clan]),
            ),
        );
    }

    /**
     * A rating made by $wins wins, then $losses losses, each against a newcomer
     * at 1000: one win makes 1016, two 1031; one loss 984, two 969.
     */
    private function rating(string $subject, RankingSubject $subjectType = RankingSubject::PLAYER, int $wins = 0, int $losses = 0): Rating
    {
        $rating = Rating::start(new RatingId(Uuid::v4()->toString()), $subjectType, $subject, new GameId(self::GAME_ID));

        for ($fight = 0; $fight < $wins + $losses; ++$fight) {
            $opponent = Rating::start(new RatingId(Uuid::v4()->toString()), $subjectType, Uuid::v4()->toString(), new GameId(self::GAME_ID));
            Rating::settle($rating, $opponent, $fight < $wins ? $rating : $opponent, new FightId(Uuid::v4()->toString()), new RatingChangeId(Uuid::v4()->toString()), new RatingChangeId(Uuid::v4()->toString()));
        }

        return $rating;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, limit: int, pages: int}
     */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
