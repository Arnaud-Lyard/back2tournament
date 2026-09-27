<?php

declare(strict_types=1);

namespace App\Tests\Competition\Ranking\Application;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Ranking\Application\Model\FindRatingQuery;
use App\Competition\Ranking\Application\Service\FindRatingHandler;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Entity\RatingChangeId;
use App\Competition\Ranking\Domain\Entity\RatingId;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
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

    public function test_a_ranked_player_profile_reads_its_rating_its_record_and_its_rank(): void
    {
        $rating = $this->rating(RankingSubject::PLAYER, self::PLAYER_ID);

        $read = $this->read($this->handler([$rating], above: 2, total: 9)(FindRatingQuery::ofPlayer(self::PLAYER_ID)));

        $this->assertSame(
            [
                'subject' => ['type' => 'player', 'id' => ['value' => self::PLAYER_ID], 'name' => 'Leader#0001', 'tag' => null],
                'game' => ['value' => self::GAME_ID],
                'rank' => 3,
                'total' => 9,
                'rating' => 1016,
                'fights' => 1,
                'wins' => 1,
                'draws' => 0,
                'losses' => 0,
            ],
            $read,
        );
    }

    public function test_a_profile_with_no_settled_fight_stands_at_1000_unranked(): void
    {
        $read = $this->read($this->handler([], above: 0, total: 9)(FindRatingQuery::ofPlayer(self::PLAYER_ID)));

        $this->assertSame([1000, 0, null, 9], [$read['rating'], $read['fights'], $read['rank'], $read['total']]);
    }

    public function test_a_clan_reads_its_rating_under_its_name_and_tag(): void
    {
        $read = $this->read($this->handler([$this->rating(RankingSubject::CLAN, self::CLAN_ID)], above: 0, total: 2)(FindRatingQuery::ofClan(self::CLAN_ID)));

        $this->assertSame(['type' => 'clan', 'id' => ['value' => self::CLAN_ID], 'name' => 'Back to Tournament', 'tag' => 'B2T'], $read['subject']);
        $this->assertSame([1, 1016], [$read['rank'], $read['rating']]);
    }

    public function test_an_unknown_player_profile_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageIsOrContains('player profile not found');

        $this->handler([], above: 0, total: 0)(FindRatingQuery::ofPlayer('55555555-5555-4555-8555-555555555555'));
    }

    public function test_an_unknown_clan_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageIsOrContains('clan not found');

        $this->handler([], above: 0, total: 0)(FindRatingQuery::ofClan('55555555-5555-4555-8555-555555555555'));
    }

    public function test_an_id_that_is_no_uuid_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler([], above: 0, total: 0)(FindRatingQuery::ofClan('nope'));
    }

    /**
     * @param list<Rating> $ratings
     */
    private function handler(array $ratings, int $above, int $total): FindRatingHandler
    {
        $ratingRepository = $this->repositoryStub(RatingRepositoryInterface::class, $ratings);
        $ratingRepository->method('countAbove')->willReturn($above);
        $ratingRepository->method('countRanking')->willReturn($total);

        return new FindRatingHandler(
            $ratingRepository,
            $this->repositoryStub(PlayerRepositoryInterface::class, [self::aPlayer(self::PLAYER_ID, self::USER_ID, self::GAME_ID, 'Leader#0001')]),
            $this->repositoryStub(ClanRepositoryInterface::class, [self::aClan(self::CLAN_ID, self::GAME_ID, self::PLAYER_ID)]),
        );
    }

    /**
     * One win against a newcomer: 1016.
     */
    private function rating(RankingSubject $subjectType, string $subject): Rating
    {
        $rating = Rating::start(new RatingId(Uuid::v4()->toString()), $subjectType, $subject, new GameId(self::GAME_ID));
        $opponent = Rating::start(new RatingId(Uuid::v4()->toString()), $subjectType, Uuid::v4()->toString(), new GameId(self::GAME_ID));
        Rating::settle($rating, $opponent, $rating, new FightId(Uuid::v4()->toString()), new RatingChangeId(Uuid::v4()->toString()), new RatingChangeId(Uuid::v4()->toString()));

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
