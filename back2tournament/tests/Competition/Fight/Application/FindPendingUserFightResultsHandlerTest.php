<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Application;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Competitor\Domain\Repository\CompetitorRepositoryInterface;
use App\Competition\Fight\Application\Model\FindPendingUserFightResultsQuery;
use App\Competition\Fight\Application\Service\FindPendingUserFightResultsHandler;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FindPendingUserFightResultsHandlerTest extends TestCase
{
    private const USER_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const MY_PLAYER_ID = '33333333-3333-4333-8333-333333333333';
    private const MY_COMPETITOR_ID = '44444444-4444-4444-8444-444444444444';
    private const THEIR_PLAYER_ID = '55555555-5555-4555-8555-555555555555';
    private const THEIR_COMPETITOR_ID = '66666666-6666-4666-8666-666666666666';
    private const FIGHT_ID = '77777777-7777-4777-8777-777777777777';
    private const RESULT_ID = '88888888-8888-4888-8888-888888888888';

    public function test_a_caller_without_player_profile_reads_an_empty_page_and_costs_no_result_query(): void
    {
        $resultRepository = $this->createMock(ResultRepositoryInterface::class);
        $resultRepository->expects($this->never())->method('findBy');
        $resultRepository->expects($this->never())->method('count');

        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findBy')->willReturn([]);

        $handler = new FindPendingUserFightResultsHandler(
            $this->currentUserProvider(),
            $playerRepository,
            $this->createStub(CompetitorRepositoryInterface::class),
            $this->createStub(FightRepositoryInterface::class),
            $resultRepository,
            $this->createStub(NormalizerInterface::class),
        );

        $page = $this->read($handler(new FindPendingUserFightResultsQuery(1, 10)));

        $this->assertSame([], $page['items']);
        $this->assertSame(0, $page['total']);
        $this->assertSame(0, $page['pages']);
    }

    public function test_each_result_carries_the_game_the_caller_s_own_profile_and_the_other_side(): void
    {
        $page = $this->read($this->handler()(new FindPendingUserFightResultsQuery(1, 10)));

        $this->assertCount(1, $page['items']);

        $item = $page['items'][0];
        $this->assertSame(['value' => self::GAME_ID], $item['game']);
        $this->assertSame(
            ['id' => ['value' => self::MY_PLAYER_ID], 'battletag' => 'Mine#1111'],
            $item['player'],
        );
        $this->assertSame(
            [
                'competitor' => ['value' => self::THEIR_COMPETITOR_ID],
                'player' => ['id' => ['value' => self::THEIR_PLAYER_ID], 'battletag' => 'Theirs#2222'],
            ],
            $item['opponent'],
        );
    }

    public function test_the_other_side_is_the_competitor_the_caller_is_not(): void
    {
        $fight = Fight::create(
            new FightId(self::FIGHT_ID),
            new CompetitorId(self::THEIR_COMPETITOR_ID),
            new CompetitorId(self::MY_COMPETITOR_ID),
        );

        $page = $this->read($this->handler($fight)(new FindPendingUserFightResultsQuery(1, 10)));

        $this->assertSame(
            ['value' => self::THEIR_COMPETITOR_ID],
            $page['items'][0]['opponent']['competitor'],
        );
    }

    public function test_an_opponent_that_is_not_a_player_carries_no_profile(): void
    {
        $page = $this->read($this->handler(null, CompetitorType::TEAM)(new FindPendingUserFightResultsQuery(1, 10)));

        $this->assertNull($page['items'][0]['opponent']['player']);
    }

    public function test_the_page_carries_the_total_and_how_many_pages_it_yields(): void
    {
        $page = $this->read($this->handler(null, CompetitorType::PLAYER, 42)(new FindPendingUserFightResultsQuery(2, 10)));

        $this->assertSame(42, $page['total']);
        $this->assertSame(2, $page['page']);
        $this->assertSame(10, $page['limit']);
        $this->assertSame(5, $page['pages']);
    }

    public function test_only_the_callers_competitors_and_the_unsettled_statuses_are_asked_for(): void
    {
        $resultRepository = $this->createMock(ResultRepositoryInterface::class);
        $resultRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'competitor' => [self::MY_COMPETITOR_ID],
                    'status' => [ResultStatus::PENDING, ResultStatus::REPORTING],
                ],
                ['createdAt' => 'ASC'],
                10,
                10,
            )
            ->willReturn([]);
        $resultRepository->method('count')->willReturn(0);

        $handler = new FindPendingUserFightResultsHandler(
            $this->currentUserProvider(),
            $this->playerRepository(),
            $this->competitorRepository(CompetitorType::PLAYER),
            $this->createStub(FightRepositoryInterface::class),
            $resultRepository,
            $this->normalizer(),
        );

        $handler(new FindPendingUserFightResultsQuery(2, 10));
    }

    private function handler(
        ?Fight $fight = null,
        CompetitorType $opponentType = CompetitorType::PLAYER,
        int $total = 1,
    ): FindPendingUserFightResultsHandler {
        $fight ??= Fight::create(
            new FightId(self::FIGHT_ID),
            new CompetitorId(self::MY_COMPETITOR_ID),
            new CompetitorId(self::THEIR_COMPETITOR_ID),
        );

        $fightRepository = $this->createStub(FightRepositoryInterface::class);
        $fightRepository->method('findBy')->willReturn([$fight]);

        $resultRepository = $this->createStub(ResultRepositoryInterface::class);
        $resultRepository->method('findBy')->willReturn([$this->myResult($fight)]);
        $resultRepository->method('count')->willReturn($total);

        return new FindPendingUserFightResultsHandler(
            $this->currentUserProvider(),
            $this->playerRepository(),
            $this->competitorRepository($opponentType),
            $fightRepository,
            $resultRepository,
            $this->normalizer(),
        );
    }

    private function myResult(Fight $fight): Result
    {
        return Fight::createResult($fight, new ResultId(self::RESULT_ID), new CompetitorId(self::MY_COMPETITOR_ID));
    }

    private function currentUserProvider(): CurrentUserProviderInterface
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn(new User(self::USER_ID));

        return $currentUserProvider;
    }

    private function playerRepository(): PlayerRepositoryInterface
    {
        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findBy')->willReturnCallback(
            fn (array $criteria): array => isset($criteria['user'])
                ? [$this->player(self::MY_PLAYER_ID, 'Mine#1111')]
                : [$this->player(self::THEIR_PLAYER_ID, 'Theirs#2222')]
        );

        return $playerRepository;
    }

    private function competitorRepository(CompetitorType $opponentType): CompetitorRepositoryInterface
    {
        $mine = Competitor::create(
            new CompetitorId(self::MY_COMPETITOR_ID),
            CompetitorType::PLAYER,
            self::MY_PLAYER_ID,
        );
        $theirs = Competitor::create(
            new CompetitorId(self::THEIR_COMPETITOR_ID),
            $opponentType,
            self::THEIR_PLAYER_ID,
        );

        $competitorRepository = $this->createStub(CompetitorRepositoryInterface::class);
        $competitorRepository->method('findBy')->willReturnCallback(
            static fn (array $criteria): array => isset($criteria['reference']) ? [$mine] : [$theirs]
        );

        return $competitorRepository;
    }

    private function normalizer(): NormalizerInterface
    {
        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturnCallback(
            static fn (Result $result): array => [
                'id' => ['value' => $result->getId()->getValue()],
                'fight' => ['value' => $result->getFight()->getValue()],
                'competitor' => ['value' => $result->getCompetitor()->getValue()],
            ]
        );

        return $normalizer;
    }

    private function player(string $id, string $battletag): Player
    {
        return Player::create(
            new PlayerId($id),
            $battletag,
            new GameId(self::GAME_ID),
            new UserId(self::USER_ID),
        );
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, limit: int, pages: int}
     */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
