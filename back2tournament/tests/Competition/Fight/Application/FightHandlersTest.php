<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Application;

use App\Competition\Fight\Application\Model\ConfirmFightResultsCommand;
use App\Competition\Fight\Application\Model\CreateFightCommand;
use App\Competition\Fight\Application\Model\DeclareFightResultsCommand;
use App\Competition\Fight\Application\Model\FindPendingUserFightResultsQuery;
use App\Competition\Fight\Application\Service\ConfirmFightResultsHandler;
use App\Competition\Fight\Application\Service\CreateFightHandler;
use App\Competition\Fight\Application\Service\DeclareFightResultsHandler;
use App\Competition\Fight\Application\Service\FindPendingUserFightResultsHandler;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Entity\Score;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Event\FightSettledEvent;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayer;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Competition\Shared\Domain\Provider\FightSchedulerInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class FightHandlersTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const OTHER_GAME_ID = '12121212-1212-4121-8121-121212121212';
    private const MY_USER = '22222222-2222-4222-8222-222222222222';
    private const MY_PLAYER = '23232323-2323-4232-8232-232323232323';
    private const MATE_PLAYER = '24242424-2424-4242-8242-242424242424';
    private const THEIR_USER = '33333333-3333-4333-8333-333333333333';
    private const THEIR_PLAYER = '34343434-3434-4343-8343-343434343434';
    private const THEIR_MATE_PLAYER = '35353535-3535-4353-8353-353535353535';
    private const STRANGER_USER = '44444444-4444-4444-8444-444444444444';
    private const STRANGER_PLAYER = '45454545-4545-4545-8545-454545454545';
    private const MY_CLAN = '55555555-5555-4555-8555-555555555555';
    private const THEIR_CLAN = '56565656-5656-4565-8565-565656565656';
    private const MY_TEAM = '66666666-6666-4666-8666-666666666666';
    private const THEIR_TEAM = '67676767-6767-4676-8676-676767676767';
    private const MY_SIDE = '77777777-7777-4777-8777-777777777777';
    private const THEIR_SIDE = '78787878-7878-4787-8787-787878787878';
    private const FIGHT_ID = '88888888-8888-4888-8888-888888888888';
    private const MY_RESULT = '99999999-9999-4999-8999-999999999999';
    private const THEIR_RESULT = '9a9a9a9a-9a9a-49a9-89a9-9a9a9a9a9a9a';

    public function test_a_player_challenges_another_player_of_the_game(): void
    {
        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->once())
            ->method('schedule')
            ->with('competitor-of-'.self::MY_PLAYER, 'competitor-of-'.self::THEIR_PLAYER, self::GAME_ID, 1)
            ->willReturn($this->fight());

        $this->createHandler($scheduler, self::MY_USER)(new CreateFightCommand(false, self::MY_PLAYER, self::THEIR_PLAYER));
    }

    public function test_a_bystander_opens_no_fight(): void
    {
        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->never())->method('schedule');

        $this->expectException(PermissionDeniedException::class);

        $this->createHandler($scheduler, self::STRANGER_USER)(new CreateFightCommand(false, self::MY_PLAYER, self::THEIR_PLAYER));
    }

    public function test_players_of_two_games_do_not_fight(): void
    {
        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->never())->method('schedule');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('same game');

        $this->createHandler($scheduler, self::MY_USER, extraPlayers: [
            self::aPlayer(self::STRANGER_PLAYER, self::STRANGER_USER, self::OTHER_GAME_ID),
        ])(new CreateFightCommand(false, self::MY_PLAYER, self::STRANGER_PLAYER));
    }

    public function test_a_game_not_played_one_versus_one_has_no_duel(): void
    {
        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->never())->method('schedule');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('is not played 1v1');

        $this->createHandler($scheduler, self::MY_USER, teamSizes: [5])(new CreateFightCommand(false, self::MY_PLAYER, self::THEIR_PLAYER));
    }

    public function test_a_team_leader_challenges_a_team_of_the_same_format(): void
    {
        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->once())
            ->method('schedule')
            ->with('competitor-of-'.self::MY_TEAM, 'competitor-of-'.self::THEIR_TEAM, self::GAME_ID, 2)
            ->willReturn($this->fight(2));

        $this->createHandler($scheduler, self::MY_USER)(new CreateFightCommand(true, self::MY_TEAM, self::THEIR_TEAM));
    }

    public function test_a_team_member_who_does_not_lead_opens_no_team_fight(): void
    {
        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->never())->method('schedule');

        $this->expectException(PermissionDeniedException::class);

        // STRANGER_USER owns MATE_PLAYER, who plays in MY_TEAM without leading it.
        $this->createHandler($scheduler, self::STRANGER_USER)(new CreateFightCommand(true, self::MY_TEAM, self::THEIR_TEAM));
    }

    public function test_teams_of_two_formats_do_not_fight(): void
    {
        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->never())->method('schedule');

        $theirClan = self::aClan(self::THEIR_CLAN, self::GAME_ID, self::THEIR_PLAYER, 'OTH');
        [$trio, $trioLineup] = self::aTeam(self::THEIR_TEAM, $theirClan, [self::THEIR_PLAYER, self::THEIR_MATE_PLAYER, self::STRANGER_PLAYER]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('same format');

        $this->createHandler($scheduler, self::MY_USER, theirTeam: $trio, theirLineup: $trioLineup)(
            new CreateFightCommand(true, self::MY_TEAM, self::THEIR_TEAM)
        );
    }

    public function test_a_player_never_stands_on_both_sides(): void
    {
        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->never())->method('schedule');

        $theirClan = self::aClan(self::THEIR_CLAN, self::GAME_ID, self::THEIR_PLAYER, 'OTH');
        [$overlapping, $lineup] = self::aTeam(self::THEIR_TEAM, $theirClan, [self::THEIR_PLAYER, self::MATE_PLAYER]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('both sides');

        $this->createHandler($scheduler, self::MY_USER, theirTeam: $overlapping, theirLineup: $lineup)(
            new CreateFightCommand(true, self::MY_TEAM, self::THEIR_TEAM)
        );
    }

    public function test_the_caller_declares_the_scores_from_their_side(): void
    {
        [$fight, $mine, $theirs] = $this->openFight();

        $resultRepository = $this->repositoryMock(ResultRepositoryInterface::class, [$mine, $theirs]);
        $resultRepository->expects($this->exactly(2))->method('save');

        $view = $this->read($this->declareHandler($fight, $resultRepository, [self::MY_SIDE])(
            new DeclareFightResultsCommand(self::FIGHT_ID, 3, 1)
        ));

        $this->assertSame('reporting', $view['status']);
        $this->assertSame(self::MY_SIDE, $view['declaredBy']['value']);
        $this->assertSame(self::MY_SIDE, $view['mySide']['value']);
        $this->assertSame(ResultStatus::WIN, $mine->getReportedStatus());
        $this->assertSame(1, $theirs->getScore());
    }

    public function test_a_bystander_declares_nothing_and_nothing_is_written(): void
    {
        [$fight, $mine, $theirs] = $this->openFight();

        $resultRepository = $this->repositoryMock(ResultRepositoryInterface::class, [$mine, $theirs]);
        $resultRepository->expects($this->never())->method('save');

        $this->expectException(PermissionDeniedException::class);

        $this->declareHandler($fight, $resultRepository, [self::STRANGER_PLAYER])(new DeclareFightResultsCommand(self::FIGHT_ID, 3, 1));
    }

    public function test_a_negative_score_costs_no_query(): void
    {
        $fightRepository = $this->createMock(FightRepositoryInterface::class);
        $fightRepository->expects($this->never())->method('findOneBy');

        $this->expectException(ValidationException::class);

        new DeclareFightResultsHandler(
            $fightRepository,
            $this->createStub(ResultRepositoryInterface::class),
            $this->registry([self::MY_SIDE]),
            $this->signedIn(self::MY_USER),
            $this->createStub(EventDispatcherInterface::class),
        )(new DeclareFightResultsCommand(self::FIGHT_ID, -1, 1));
    }

    public function test_an_unknown_fight_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $this->declareHandler(null, $this->repositoryStub(ResultRepositoryInterface::class, []), [self::MY_SIDE])(
            new DeclareFightResultsCommand(self::FIGHT_ID, 3, 1)
        );
    }

    public function test_the_other_side_confirms_and_the_settlement_is_announced(): void
    {
        [$fight, $mine, $theirs] = $this->openFight();
        Fight::declareOutcome($fight, new CompetitorId(self::THEIR_SIDE), $theirs, new Score(2), $mine, new Score(0));
        $fight->pullDomainEvents();

        $dispatched = [];
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (object $event) use (&$dispatched): object {
                $dispatched[] = $event;

                return $event;
            }
        );

        $resultRepository = $this->repositoryMock(ResultRepositoryInterface::class, [$mine, $theirs]);
        $resultRepository->expects($this->exactly(2))->method('save');

        $view = $this->read($this->confirmHandler($fight, $resultRepository, [self::MY_SIDE], $eventDispatcher)(
            new ConfirmFightResultsCommand(self::FIGHT_ID)
        ));

        $this->assertSame('finished', $view['status']);
        $this->assertSame(self::THEIR_SIDE, $view['winner']['value']);
        $this->assertSame(ResultStatus::LOSS, $mine->getStatus());
        $this->assertSame(ResultStatus::WIN, $theirs->getStatus());
        $this->assertCount(1, array_filter($dispatched, static fn (object $event): bool => $event instanceof FightSettledEvent));
    }

    public function test_the_declaring_side_confirms_nothing_and_nothing_is_written(): void
    {
        [$fight, $mine, $theirs] = $this->openFight();
        Fight::declareOutcome($fight, new CompetitorId(self::MY_SIDE), $mine, new Score(2), $theirs, new Score(0));

        $resultRepository = $this->repositoryMock(ResultRepositoryInterface::class, [$mine, $theirs]);
        $resultRepository->expects($this->never())->method('save');

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('cannot confirm its own outcome');

        $this->confirmHandler($fight, $resultRepository, [self::MY_SIDE])(new ConfirmFightResultsCommand(self::FIGHT_ID));
    }

    public function test_a_caller_who_speaks_for_nobody_reads_an_empty_page_and_costs_no_result_query(): void
    {
        $resultRepository = $this->createMock(ResultRepositoryInterface::class);
        $resultRepository->expects($this->never())->method('findBy');
        $resultRepository->expects($this->never())->method('count');

        $page = $this->read($this->pendingHandler([], $resultRepository)(new FindPendingUserFightResultsQuery(1, 10)));

        $this->assertSame([], $page['items']);
        $this->assertSame(0, $page['total']);
        $this->assertSame(0, $page['pages']);
    }

    public function test_a_waiting_result_names_both_sides_and_who_declared(): void
    {
        [$fight, $mine, $theirs] = $this->openFight(2);
        Fight::declareOutcome($fight, new CompetitorId(self::THEIR_SIDE), $theirs, new Score(2), $mine, new Score(0));

        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturnCallback(
            static fn (Result $result): array => ['id' => ['value' => $result->getId()->getValue()], 'status' => $result->getStatus()->value]
        );

        $page = $this->read($this->pendingHandler(
            [self::MY_SIDE],
            $this->repositoryStub(ResultRepositoryInterface::class, [$mine, $theirs]),
            $this->repositoryStub(FightRepositoryInterface::class, [$fight]),
            $normalizer,
        )(new FindPendingUserFightResultsQuery(1, 10)));

        $this->assertSame(1, $page['total']);
        $item = $page['items'][0];
        $this->assertSame(self::MY_RESULT, $item['id']['value']);
        $this->assertSame('reporting', $item['status']);
        $this->assertSame(2, $item['teamSize']);
        $this->assertSame(self::THEIR_SIDE, $item['declaredBy']['value']);
        $this->assertSame('Falcons', $item['side']['name']);
        $this->assertNull($item['player']);
        $this->assertSame('Ravens', $item['opponent']['name']);
        $this->assertSame('team', $item['opponent']['type']);
    }

    /**
     * @param list<int>             $teamSizes
     * @param list<Player>          $extraPlayers
     * @param list<TeamPlayer>|null $theirLineup
     */
    private function createHandler(
        FightSchedulerInterface $scheduler,
        string $caller,
        array $teamSizes = [1, 2],
        array $extraPlayers = [],
        ?Team $theirTeam = null,
        ?array $theirLineup = null,
    ): CreateFightHandler {
        $myClan = self::aClan(self::MY_CLAN, self::GAME_ID, self::MY_PLAYER);
        $theirClan = self::aClan(self::THEIR_CLAN, self::GAME_ID, self::THEIR_PLAYER, 'OTH');
        [$myTeam, $myLineup] = self::aTeam(self::MY_TEAM, $myClan, [self::MY_PLAYER, self::MATE_PLAYER]);
        [$defaultTeam, $defaultLineup] = self::aTeam(self::THEIR_TEAM, $theirClan, [self::THEIR_PLAYER, self::THEIR_MATE_PLAYER]);

        $registry = $this->createStub(CompetitorRegistryInterface::class);
        $registry->method('enlistPlayer')->willReturnCallback(static fn (string $id): string => 'competitor-of-'.$id);
        $registry->method('enlistTeam')->willReturnCallback(static fn (string $id): string => 'competitor-of-'.$id);
        $registry->method('describe')->willReturn([]);
        $registry->method('representedBy')->willReturn([]);

        return new CreateFightHandler(
            $this->repositoryStub(PlayerRepositoryInterface::class, array_merge([
                self::aPlayer(self::MY_PLAYER, self::MY_USER, self::GAME_ID),
                self::aPlayer(self::MATE_PLAYER, self::STRANGER_USER, self::GAME_ID),
                self::aPlayer(self::THEIR_PLAYER, self::THEIR_USER, self::GAME_ID),
            ], $extraPlayers)),
            $this->repositoryStub(TeamRepositoryInterface::class, [$myTeam, $theirTeam ?? $defaultTeam]),
            $this->repositoryStub(TeamPlayerRepositoryInterface::class, array_merge($myLineup, $theirLineup ?? $defaultLineup)),
            $this->repositoryStub(GameRepositoryInterface::class, [self::aGame(self::GAME_ID, $teamSizes)]),
            $registry,
            $scheduler,
            $this->signedIn($caller),
        );
    }

    /**
     * @param list<string> $represented
     */
    private function declareHandler(?Fight $fight, ResultRepositoryInterface $resultRepository, array $represented): DeclareFightResultsHandler
    {
        return new DeclareFightResultsHandler(
            $this->repositoryStub(FightRepositoryInterface::class, null === $fight ? [] : [$fight]),
            $resultRepository,
            $this->registry($represented),
            $this->signedIn(self::MY_USER),
            $this->createStub(EventDispatcherInterface::class),
        );
    }

    /**
     * @param list<string> $represented
     */
    private function confirmHandler(
        Fight $fight,
        ResultRepositoryInterface $resultRepository,
        array $represented,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): ConfirmFightResultsHandler {
        return new ConfirmFightResultsHandler(
            $resultRepository,
            $this->repositoryStub(FightRepositoryInterface::class, [$fight]),
            $this->registry($represented),
            $this->signedIn(self::MY_USER),
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
        );
    }

    /**
     * @param list<string> $represented
     */
    private function pendingHandler(
        array $represented,
        ResultRepositoryInterface $resultRepository,
        ?FightRepositoryInterface $fightRepository = null,
        ?NormalizerInterface $normalizer = null,
    ): FindPendingUserFightResultsHandler {
        return new FindPendingUserFightResultsHandler(
            $this->signedIn(self::MY_USER),
            $this->registry($represented),
            $fightRepository ?? $this->createStub(FightRepositoryInterface::class),
            $resultRepository,
            $normalizer ?? $this->createStub(NormalizerInterface::class),
        );
    }

    /**
     * @param list<string> $represented
     */
    private function registry(array $represented): CompetitorRegistryInterface
    {
        $registry = $this->createStub(CompetitorRegistryInterface::class);
        $registry->method('representedBy')->willReturn($represented);
        $registry->method('describe')->willReturn([
            self::MY_SIDE => ['type' => 'team', 'reference' => self::MY_TEAM, 'name' => 'Falcons'],
            self::THEIR_SIDE => ['type' => 'team', 'reference' => self::THEIR_TEAM, 'name' => 'Ravens'],
        ]);

        return $registry;
    }

    private function fight(int $teamSize = 1): Fight
    {
        return Fight::create(
            new FightId(self::FIGHT_ID),
            new CompetitorId(self::MY_SIDE),
            new CompetitorId(self::THEIR_SIDE),
            new GameId(self::GAME_ID),
            new TeamSize($teamSize),
        );
    }

    /**
     * @return array{Fight, Result, Result}
     */
    private function openFight(int $teamSize = 1): array
    {
        $fight = $this->fight($teamSize);

        return [
            $fight,
            Fight::createResult($fight, new ResultId(self::MY_RESULT), new CompetitorId(self::MY_SIDE)),
            Fight::createResult($fight, new ResultId(self::THEIR_RESULT), new CompetitorId(self::THEIR_SIDE)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
