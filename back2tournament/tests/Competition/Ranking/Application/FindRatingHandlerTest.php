<?php

declare(strict_types=1);

namespace App\Tests\Competition\Ranking\Application;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Ranking\Application\Model\FindRatingQuery;
use App\Competition\Ranking\Application\Service\FindRatingHandler;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Entity\RatingChangeId;
use App\Competition\Ranking\Domain\Entity\RatingId;
use App\Competition\Ranking\Domain\Enum\FightOutcome;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use App\Competition\Shared\Domain\Provider\ClanTagProvider;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamSizeValueObject;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class FindRatingHandlerTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const PLAYER_ID = '22222222-2222-4222-8222-222222222222';
    private const USER_ID = '33333333-3333-4333-8333-333333333333';
    private const CLAN_ID = '44444444-4444-4444-8444-444444444444';

    public function test_a_player_profile_reads_one_rating_per_format_of_its_game(): void
    {
        $in2v2 = $this->rating(RankingSubject::PLAYER, self::PLAYER_ID, teamSize: 2);

        $read = $this->read($this->handler([$in2v2], above: [2 => 2], total: [1 => 9, 2 => 4])(FindRatingQuery::ofPlayer(self::PLAYER_ID)));

        $this->assertSame(
            [
                'subject' => ['type' => 'player', 'id' => ['value' => self::PLAYER_ID], 'name' => 'Leader#0001', 'tag' => 'B2T'],
                'game' => ['value' => self::GAME_ID],
                'ratings' => [
                    ['teamSize' => 1, 'rank' => null, 'total' => 9, 'rating' => 1000, 'fights' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0],
                    ['teamSize' => 2, 'rank' => 3, 'total' => 4, 'rating' => 1016, 'fights' => 1, 'wins' => 1, 'draws' => 0, 'losses' => 0],
                ],
            ],
            $read,
        );
    }

    public function test_a_clan_reads_its_ratings_under_its_name_and_tag(): void
    {
        $read = $this->read($this->handler(
            [$this->rating(RankingSubject::CLAN, self::CLAN_ID, teamSize: 1)],
            above: [1 => 0],
            total: [1 => 2, 2 => 0],
        )(FindRatingQuery::ofClan(self::CLAN_ID)));

        $this->assertSame(['type' => 'clan', 'id' => ['value' => self::CLAN_ID], 'name' => 'Back to Tournament', 'tag' => 'B2T'], $read['subject']);
        $this->assertSame([[1, 1, 1016], [2, null, 1000]], array_map(
            static fn (array $rating): array => [$rating['teamSize'], $rating['rank'], $rating['rating']],
            $read['ratings'],
        ));
    }

    public function test_a_rating_of_another_subject_or_player_is_not_read(): void
    {
        $someoneElse = $this->rating(RankingSubject::PLAYER, '55555555-5555-4555-8555-555555555555', teamSize: 1);
        // A clan and a player profile never share an id, but a rating reads by both.
        $sameIdAsClan = $this->rating(RankingSubject::CLAN, self::PLAYER_ID, teamSize: 1);

        $read = $this->read($this->handler([$someoneElse, $sameIdAsClan], above: [], total: [])(FindRatingQuery::ofPlayer(self::PLAYER_ID)));

        $this->assertSame([null, null], array_column($read['ratings'], 'rank'));
    }

    public function test_an_unknown_player_profile_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageIsOrContains('player profile not found');

        $this->handler([], above: [], total: [])(FindRatingQuery::ofPlayer('55555555-5555-4555-8555-555555555555'));
    }

    public function test_an_unknown_clan_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageIsOrContains('clan not found');

        $this->handler([], above: [], total: [])(FindRatingQuery::ofClan('55555555-5555-4555-8555-555555555555'));
    }

    public function test_an_id_that_is_no_uuid_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler([], above: [], total: [])(FindRatingQuery::ofClan('nope'));
    }

    /**
     * @param list<Rating>     $ratings
     * @param array<int, int> $above   per format, how many rate higher
     * @param array<int, int> $total   per format, how many are ranked
     */
    private function handler(array $ratings, array $above, array $total): FindRatingHandler
    {
        $ratingRepository = $this->repositoryStub(RatingRepositoryInterface::class, $ratings);
        $ratingRepository->method('countAbove')->willReturnCallback(
            static fn (RankingSubject $subjectType, string $gameId, int $teamSize, int $value): int => $above[$teamSize] ?? 0
        );
        $ratingRepository->method('countRanking')->willReturnCallback(
            static fn (RankingSubject $subjectType, string $gameId, int $teamSize): int => $total[$teamSize] ?? 0
        );

        // The profile leads the clan: its tag is the clan's.
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::PLAYER_ID);

        return new FindRatingHandler(
            $ratingRepository,
            $this->repositoryStub(GameRepositoryInterface::class, [self::aGame(self::GAME_ID, [1, 2])]),
            $this->repositoryStub(PlayerRepositoryInterface::class, [self::aPlayer(self::PLAYER_ID, self::USER_ID, self::GAME_ID, 'Leader#0001')]),
            $this->repositoryStub(ClanRepositoryInterface::class, [$clan]),
            new ClanTagProvider(
                $this->repositoryStub(ClanMemberRepositoryInterface::class, [self::leadership($clan)]),
                $this->repositoryStub(ClanRepositoryInterface::class, [$clan]),
            ),
        );
    }

    /**
     * One win against a newcomer: 1016.
     */
    private function rating(RankingSubject $subjectType, string $subject, int $teamSize): Rating
    {
        $rating = Rating::start(new RatingId(Uuid::v4()->toString()), $subjectType, $subject, new GameId(self::GAME_ID), new TeamSizeValueObject($teamSize));
        $newcomer = Rating::start(new RatingId(Uuid::v4()->toString()), $subjectType, Uuid::v4()->toString(), new GameId(self::GAME_ID), new TeamSizeValueObject($teamSize));
        Rating::settle(
            [$rating],
            [$newcomer],
            FightOutcome::SIDE_ONE_WON,
            new FightId(Uuid::v4()->toString()),
            [new RatingChangeId(Uuid::v4()->toString()), new RatingChangeId(Uuid::v4()->toString())],
        );

        return $rating;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
