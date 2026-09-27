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
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Provider\ClanTagProviderInterface;
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
    private const RIVAL_CLAN = '70707070-7070-4707-8707-707070707070';

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

    public function test_each_side_goes_by_the_tag_of_the_clan_it_played_for_in_that_fight(): void
    {
        // Raven played for RIVAL_CLAN then, whatever the clan it is in today.
        [$won, $mine, $theirs] = $this->settledFight(self::WON, self::MY_SIDE, self::RIVAL, 3, 1, 1, null, self::RIVAL_CLAN);

        $page = $this->read($this->handler([$won], [$mine, $theirs])(FindResultHistoryQuery::ofPlayer(self::PLAYER_ID, 1, 10)));

        $this->assertSame([null, 'RIV'], [$page['items'][0]['side']['tag'], $page['items'][0]['opponent']['tag']]);
    }

    public function test_a_clan_history_tells_its_team_fights_and_its_members_duels_from_its_side(): void
    {
        [$teamFight, $myTeam, $rivalTeam] = $this->settledFight(self::TEAM_FIGHT, self::MY_TEAM_SIDE, self::RIVAL_TEAM, 2, 2, 2, self::CLAN_ID, self::RIVAL_CLAN);
        [$duel, $rival, $member] = $this->settledFight(self::LOST, self::RIVAL, self::MY_SIDE, 3, 1, 1, self::RIVAL_CLAN, self::CLAN_ID);

        $resultRepository = $this->repositoryStub(ResultRepositoryInterface::class, [$myTeam, $rivalTeam, $rival, $member]);
        $resultRepository->method('findSettledAgainstOtherClans')->willReturnCallback(
            static fn (string $clanId, int $limit, int $offset): array => self::CLAN_ID === $clanId && 0 === $offset ? [$member, $myTeam] : []
        );
        $resultRepository->method('countSettledAgainstOtherClans')->willReturn(2);

        $page = $this->read($this->handler([$teamFight, $duel], resultRepository: $resultRepository)(FindResultHistoryQuery::ofClan(self::CLAN_ID, 1, 10)));

        $this->assertSame(2, $page['total']);
        // The duel a member lost, named after the member, against another clan.
        $this->assertSame(['loss', 1, 'player', 'Demo#1000', 'B2T', 1, 'Raven#1003', 'RIV', 3], [
            $page['items'][0]['outcome'],
            $page['items'][0]['teamSize'],
            $page['items'][0]['side']['type'],
            $page['items'][0]['side']['name'],
            $page['items'][0]['side']['tag'],
            $page['items'][0]['side']['score'],
            $page['items'][0]['opponent']['name'],
            $page['items'][0]['opponent']['tag'],
            $page['items'][0]['opponent']['score'],
        ]);
        // The fight of one of its teams.
        $this->assertSame(['draw', 2, 'team', 'Demo 2v2', 'Rival 2v2'], [
            $page['items'][1]['outcome'],
            $page['items'][1]['teamSize'],
            $page['items'][1]['side']['type'],
            $page['items'][1]['side']['name'],
            $page['items'][1]['opponent']['name'],
        ]);
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
            $this->createStub(ClanTagProviderInterface::class),
        )(FindResultHistoryQuery::ofPlayer(self::PLAYER_ID, 1, 10)));

        $this->assertSame(['items' => [], 'total' => 0, 'page' => 1, 'limit' => 10, 'pages' => 0], $page);
    }

    public function test_an_id_that_is_no_uuid_is_refused_before_any_read(): void
    {
        $resultRepository = $this->createMock(ResultRepositoryInterface::class);
        $resultRepository->expects($this->never())->method('findSettledAgainstOtherClans');

        $this->expectException(ValidationException::class);

        new FindResultHistoryHandler(
            $this->createStub(CompetitorRegistryProviderInterface::class),
            $this->createStub(FightRepositoryInterface::class),
            $resultRepository,
            $this->createStub(ClanTagProviderInterface::class),
        )(FindResultHistoryQuery::ofClan('not-a-uuid', 1, 10));
    }

    /**
     * @param list<Fight>  $fights
     * @param list<Result> $results
     */
    private function handler(array $fights, array $results = [], ?ResultRepositoryInterface $resultRepository = null): FindResultHistoryHandler
    {
        $registry = $this->createStub(CompetitorRegistryProviderInterface::class);
        $registry->method('competitorsOfPlayer')->willReturn([self::MY_SIDE]);
        $registry->method('describe')->willReturn([
            self::MY_SIDE => ['type' => 'player', 'reference' => self::PLAYER_ID, 'name' => 'Demo#1000'],
            self::RIVAL => ['type' => 'player', 'reference' => self::RIVAL, 'name' => 'Raven#1003'],
            self::MY_TEAM_SIDE => ['type' => 'team', 'reference' => self::MY_TEAM_SIDE, 'name' => 'Demo 2v2'],
            self::RIVAL_TEAM => ['type' => 'team', 'reference' => self::RIVAL_TEAM, 'name' => 'Rival 2v2'],
        ]);

        // The tags of the clans as they stand; a profile's current clan does not matter.
        $clanTagProvider = $this->createStub(ClanTagProviderInterface::class);
        $clanTagProvider->method('tagsOfClans')->willReturnCallback(
            static fn (array $clanIds): array => array_intersect_key([self::CLAN_ID => 'B2T', self::RIVAL_CLAN => 'RIV'], array_flip($clanIds))
        );

        return new FindResultHistoryHandler(
            $registry,
            $this->repositoryStub(FightRepositoryInterface::class, $fights),
            $resultRepository ?? $this->repositoryStub(ResultRepositoryInterface::class, $results),
            $clanTagProvider,
        );
    }

    /**
     * @return array{Fight, Result, Result}
     */
    private function openFight(string $fightId, string $one, string $two, int $teamSize = 1, ?string $clanOne = null, ?string $clanTwo = null): array
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
            Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), new CompetitorId($one), null === $clanOne ? null : new ClanId($clanOne)),
            Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), new CompetitorId($two), null === $clanTwo ? null : new ClanId($clanTwo)),
        ];
    }

    /**
     * Declared by the first side, confirmed by the second; each side played
     * for the clan given, if any.
     *
     * @return array{Fight, Result, Result}
     */
    private function settledFight(string $fightId, string $one, string $two, int $scoreOne, int $scoreTwo, int $teamSize = 1, ?string $clanOne = null, ?string $clanTwo = null): array
    {
        [$fight, $resultOne, $resultTwo] = $this->openFight($fightId, $one, $two, $teamSize, $clanOne, $clanTwo);

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
