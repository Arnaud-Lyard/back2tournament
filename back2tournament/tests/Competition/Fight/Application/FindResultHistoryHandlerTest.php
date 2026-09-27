<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Application;

use App\Competition\Fight\Application\Model\FindResultHistoryQuery;
use App\Competition\Fight\Application\Service\FindResultHistoryHandler;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Entity\Score;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamSizeValueObject;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class FindResultHistoryHandlerTest extends TestCase
{
    use RepositoryStubs;

    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const PLAYER_ID = '22222222-2222-4222-8222-222222222222';
    private const CLAN_ID = '33333333-3333-4333-8333-333333333333';
    private const MY_SIDE = '44444444-4444-4444-8444-444444444444';
    private const MY_TEAM_SIDE = '45454545-4545-4545-8545-454545454545';
    private const RIVAL = '55555555-5555-4555-8555-555555555555';
    private const RIVAL_TEAM = '56565656-5656-4565-8565-565656565656';
    private const WON = '66666666-6666-4666-8666-666666666666';
    private const LOST = '67676767-6767-4676-8676-676767676767';
    private const OPEN = '68686868-6868-4686-8686-686868686868';
    private const TEAM_FIGHT = '69696969-6969-4696-8696-696969696969';

    public function test_a_player_history_tells_each_settled_fight_from_its_side(): void
    {
        [$won, $wonMine, $wonTheirs] = $this->settledFight(self::WON, self::MY_SIDE, self::RIVAL, 3, 1);
        [$lost, $lostTheirs, $lostMine] = $this->settledFight(self::LOST, self::RIVAL, self::MY_SIDE, 2, 0);
        [$open, $openMine, $openTheirs] = $this->openFight(self::OPEN, self::MY_SIDE, self::RIVAL);

        $page = $this->read($this->handler(
            [$won, $lost, $open],
            [$wonMine, $wonTheirs, $lostTheirs, $lostMine, $openMine, $openTheirs],
        )(FindResultHistoryQuery::ofPlayer(self::PLAYER_ID, 1, 10)));

        // The fight still waiting for its scores is no result yet.
        $this->assertSame(2, $page['total']);
        $items = array_column($page['items'], null, 'outcome');
        $this->assertEqualsCanonicalizing(['win', 'loss'], array_keys($items));

        $this->assertSame(self::WON, $items['win']['fight']['value']);
        $this->assertSame(['value' => self::MY_SIDE], $items['win']['side']['competitor']);
        $this->assertSame('Demo#1000', $items['win']['side']['name']);
        $this->assertSame(3, $items['win']['side']['score']);
        $this->assertSame('Raven#1003', $items['win']['opponent']['name']);
        $this->assertSame(1, $items['win']['opponent']['score']);

        // Told from the profile's side even when the other side opened the fight.
        $this->assertSame(self::LOST, $items['loss']['fight']['value']);
        $this->assertSame(0, $items['loss']['side']['score']);
        $this->assertSame(2, $items['loss']['opponent']['score']);
        $this->assertSame(1, $items['loss']['teamSize']);
    }

    public function test_a_clan_history_is_told_from_its_team(): void
    {
        [$fight, $mine, $theirs] = $this->settledFight(self::TEAM_FIGHT, self::MY_TEAM_SIDE, self::RIVAL_TEAM, 2, 2, 2);

        $page = $this->read($this->handler([$fight], [$mine, $theirs])(FindResultHistoryQuery::ofClan(self::CLAN_ID, 1, 10)));

        $this->assertSame(1, $page['total']);
        $this->assertSame('draw', $page['items'][0]['outcome']);
        $this->assertSame(2, $page['items'][0]['teamSize']);
        $this->assertSame('team', $page['items'][0]['side']['type']);
        $this->assertSame('Demo 2v2', $page['items'][0]['side']['name']);
        $this->assertSame('Rival 2v2', $page['items'][0]['opponent']['name']);
    }

    public function test_a_profile_that_never_competed_reads_an_empty_page_and_costs_no_result_query(): void
    {
        $registry = $this->createStub(CompetitorRegistryProviderInterface::class);
        $registry->method('competitorsOfPlayer')->willReturn([]);

        $resultRepository = $this->createMock(ResultRepositoryInterface::class);
        $resultRepository->expects($this->never())->method('findBy');
        $resultRepository->expects($this->never())->method('count');

        $page = $this->read(new FindResultHistoryHandler(
            $registry,
            $this->createStub(FightRepositoryInterface::class),
            $resultRepository,
        )(FindResultHistoryQuery::ofPlayer(self::PLAYER_ID, 1, 10)));

        $this->assertSame(['items' => [], 'total' => 0, 'page' => 1, 'limit' => 10, 'pages' => 0], $page);
    }

    public function test_an_id_that_is_no_uuid_is_refused_before_any_read(): void
    {
        $registry = $this->createMock(CompetitorRegistryProviderInterface::class);
        $registry->expects($this->never())->method('competitorsOfClan');

        $this->expectException(ValidationException::class);

        new FindResultHistoryHandler(
            $registry,
            $this->createStub(FightRepositoryInterface::class),
            $this->createStub(ResultRepositoryInterface::class),
        )(FindResultHistoryQuery::ofClan('not-a-uuid', 1, 10));
    }

    /**
     * @param list<Fight>  $fights
     * @param list<Result> $results
     */
    private function handler(array $fights, array $results): FindResultHistoryHandler
    {
        $registry = $this->createStub(CompetitorRegistryProviderInterface::class);
        $registry->method('competitorsOfPlayer')->willReturn([self::MY_SIDE]);
        $registry->method('competitorsOfClan')->willReturn([self::MY_TEAM_SIDE]);
        $registry->method('describe')->willReturn([
            self::MY_SIDE => ['type' => 'player', 'reference' => self::PLAYER_ID, 'name' => 'Demo#1000'],
            self::RIVAL => ['type' => 'player', 'reference' => self::RIVAL, 'name' => 'Raven#1003'],
            self::MY_TEAM_SIDE => ['type' => 'team', 'reference' => self::MY_TEAM_SIDE, 'name' => 'Demo 2v2'],
            self::RIVAL_TEAM => ['type' => 'team', 'reference' => self::RIVAL_TEAM, 'name' => 'Rival 2v2'],
        ]);

        return new FindResultHistoryHandler(
            $registry,
            $this->repositoryStub(FightRepositoryInterface::class, $fights),
            $this->repositoryStub(ResultRepositoryInterface::class, $results),
        );
    }

    /**
     * @return array{Fight, Result, Result}
     */
    private function openFight(string $fightId, string $one, string $two, int $teamSize = 1): array
    {
        $fight = Fight::create(
            new FightId($fightId),
            new CompetitorId($one),
            new CompetitorId($two),
            new GameId(self::GAME_ID),
            new TeamSizeValueObject($teamSize),
        );

        return [
            $fight,
            Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), new CompetitorId($one)),
            Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), new CompetitorId($two)),
        ];
    }

    /**
     * Declared by the first side, confirmed by the second.
     *
     * @return array{Fight, Result, Result}
     */
    private function settledFight(string $fightId, string $one, string $two, int $scoreOne, int $scoreTwo, int $teamSize = 1): array
    {
        [$fight, $resultOne, $resultTwo] = $this->openFight($fightId, $one, $two, $teamSize);

        Fight::declareOutcome($fight, new CompetitorId($one), $resultOne, new Score($scoreOne), $resultTwo, new Score($scoreTwo));
        Fight::confirmOutcome($fight, new CompetitorId($two), $resultOne, $resultTwo);

        return [$fight, $resultOne, $resultTwo];
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
