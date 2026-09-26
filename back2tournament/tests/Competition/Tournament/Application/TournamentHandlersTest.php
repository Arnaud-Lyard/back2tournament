<?php

declare(strict_types=1);

namespace App\Tests\Competition\Tournament\Application;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Event\FightSettledEvent;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Competition\Shared\Domain\Provider\FightSchedulerInterface;
use App\Competition\Tournament\Application\EventSubscriber\FightSettledEventSubscriber;
use App\Competition\Tournament\Application\Model\AdvanceTournamentCommand;
use App\Competition\Tournament\Application\Model\CreateTournamentCommand;
use App\Competition\Tournament\Application\Model\RegisterParticipantCommand;
use App\Competition\Tournament\Application\Model\StartTournamentCommand;
use App\Competition\Tournament\Application\Model\WithdrawParticipantCommand;
use App\Competition\Tournament\Application\Service\AdvanceTournamentHandler;
use App\Competition\Tournament\Application\Service\CreateTournamentHandler;
use App\Competition\Tournament\Application\Service\RegisterParticipantHandler;
use App\Competition\Tournament\Application\Service\StartTournamentHandler;
use App\Competition\Tournament\Application\Service\WithdrawParticipantHandler;
use App\Competition\Tournament\Domain\Entity\Matchup;
use App\Competition\Tournament\Domain\Entity\MatchupId;
use App\Competition\Tournament\Domain\Entity\OrganizerId;
use App\Competition\Tournament\Domain\Entity\Participant;
use App\Competition\Tournament\Domain\Entity\ParticipantId;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Competition\Tournament\Domain\Entity\TournamentName;
use App\Competition\Tournament\Domain\Enum\TournamentStatus;
use App\Competition\Tournament\Domain\Repository\MatchupRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\ParticipantRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TournamentHandlersTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const TOURNAMENT_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const ORGANIZER = '33333333-3333-4333-8333-333333333333';
    private const MY_USER = '44444444-4444-4444-8444-444444444444';
    private const MY_PLAYER = '45454545-4545-4545-8545-454545454545';
    private const MATE_PLAYER = '46464646-4646-4464-8464-464646464646';
    private const THEIR_USER = '55555555-5555-4555-8555-555555555555';
    private const THEIR_PLAYER = '56565656-5656-4565-8565-565656565656';
    private const MY_CLAN = '66666666-6666-4666-8666-666666666666';
    private const MY_TEAM = '67676767-6767-4676-8676-676767676767';
    private const THEIR_TEAM = '68686868-6868-4686-8686-686868686868';
    private const FIGHT_ID = '77777777-7777-4777-8777-777777777777';

    public function test_a_tournament_is_organized_by_its_creator_in_a_format_of_the_game(): void
    {
        $saved = null;
        $tournamentRepository = $this->createStub(TournamentRepositoryInterface::class);
        $tournamentRepository->method('save')->willReturnCallback(
            static function (Tournament $tournament) use (&$saved): void {
                $saved = $tournament;
            }
        );

        $view = json_decode($this->createHandler($tournamentRepository)(
            new CreateTournamentCommand('Autumn Cup', self::GAME_ID, 2, 16, new \DateTimeImmutable('+1 week')->format(\DateTimeInterface::ATOM))
        ), true, 512, JSON_THROW_ON_ERROR);

        $this->assertInstanceOf(Tournament::class, $saved);
        $this->assertSame(self::ORGANIZER, $saved->getOrganizer()->getValue());
        $this->assertSame('upcoming', $view['status']);
        $this->assertSame(0, $view['participantCount']);
        $this->assertSame([], $view['matchups']);
    }

    public function test_a_format_the_game_is_not_played_in_holds_no_tournament(): void
    {
        $tournamentRepository = $this->createMock(TournamentRepositoryInterface::class);
        $tournamentRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('is not played 5v5');

        $this->createHandler($tournamentRepository)(
            new CreateTournamentCommand('Autumn Cup', self::GAME_ID, 5, 16, new \DateTimeImmutable('+1 week')->format(\DateTimeInterface::ATOM))
        );
    }

    public function test_a_start_date_is_an_iso_date_time(): void
    {
        $tournamentRepository = $this->createMock(TournamentRepositoryInterface::class);
        $tournamentRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('ISO 8601');

        $this->createHandler($tournamentRepository)(new CreateTournamentCommand('Autumn Cup', self::GAME_ID, 1, 16, 'next friday'));
    }

    public function test_a_player_registers_their_own_profile(): void
    {
        $tournament = $this->tournament(1);

        $participantRepository = $this->repositoryMock(ParticipantRepositoryInterface::class, []);
        $participantRepository->expects($this->once())->method('save')->with($this->callback(
            static fn (Participant $participant): bool => 1 === $participant->getSeed()
                && self::uuidFor('competitor-of-'.self::MY_PLAYER) === $participant->getCompetitor()->getValue()
        ));

        $this->registerHandler($tournament, $participantRepository, self::MY_USER)(
            new RegisterParticipantCommand(self::TOURNAMENT_ID, self::MY_PLAYER, null)
        );
    }

    public function test_nobody_registers_the_profile_of_someone_else(): void
    {
        $participantRepository = $this->repositoryMock(ParticipantRepositoryInterface::class, []);
        $participantRepository->expects($this->never())->method('save');

        $this->expectException(PermissionDeniedException::class);

        $this->registerHandler($this->tournament(1), $participantRepository, self::MY_USER)(
            new RegisterParticipantCommand(self::TOURNAMENT_ID, self::THEIR_PLAYER, null)
        );
    }

    public function test_a_team_tournament_registers_teams(): void
    {
        $participantRepository = $this->repositoryMock(ParticipantRepositoryInterface::class, []);
        $participantRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('register a team');

        $this->registerHandler($this->tournament(2), $participantRepository, self::MY_USER)(
            new RegisterParticipantCommand(self::TOURNAMENT_ID, self::MY_PLAYER, null)
        );
    }

    public function test_a_full_tournament_enlists_nobody(): void
    {
        $tournament = $this->tournament(1, capacity: 2);

        $registry = $this->createMock(CompetitorRegistryInterface::class);
        $registry->expects($this->never())->method('enlistPlayer');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('full');

        $this->registerHandler($tournament, $this->repositoryStub(ParticipantRepositoryInterface::class, [
            $this->participant(1, Uuid::v4()->toString()),
            $this->participant(2, Uuid::v4()->toString()),
        ]), self::MY_USER, $registry)(new RegisterParticipantCommand(self::TOURNAMENT_ID, self::MY_PLAYER, null));
    }

    public function test_a_player_plays_for_one_team_per_tournament(): void
    {
        $tournament = $this->tournament(2);

        $participantRepository = $this->repositoryMock(ParticipantRepositoryInterface::class, [
            $this->participant(1, 'competitor-of-'.self::THEIR_TEAM),
        ]);
        $participantRepository->expects($this->never())->method('save');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('already plays for another team');

        $this->registerHandler($tournament, $participantRepository, self::MY_USER)(
            new RegisterParticipantCommand(self::TOURNAMENT_ID, null, self::MY_TEAM)
        );
    }

    public function test_the_participant_withdraws_before_the_start(): void
    {
        $leaving = $this->participant(1, 'competitor-of-'.self::MY_PLAYER);
        $staying = $this->participant(2, 'competitor-of-'.self::THEIR_PLAYER);

        $participantRepository = $this->repositoryMock(ParticipantRepositoryInterface::class, [$leaving, $staying]);
        $participantRepository->expects($this->once())->method('remove')->with($this->identicalTo($leaving));

        $this->withdrawHandler($participantRepository, self::MY_USER)(
            new WithdrawParticipantCommand(self::TOURNAMENT_ID, $leaving->getId()->getValue())
        );

        $this->assertSame(1, $staying->getSeed());
    }

    public function test_someone_else_withdraws_nobody(): void
    {
        $leaving = $this->participant(1, 'competitor-of-'.self::MY_PLAYER);

        $participantRepository = $this->repositoryMock(ParticipantRepositoryInterface::class, [$leaving]);
        $participantRepository->expects($this->never())->method('remove');

        $this->expectException(PermissionDeniedException::class);

        $this->withdrawHandler($participantRepository, self::THEIR_USER)(
            new WithdrawParticipantCommand(self::TOURNAMENT_ID, $leaving->getId()->getValue())
        );
    }

    public function test_starting_opens_the_fights_of_the_first_round(): void
    {
        $tournament = $this->tournament(1);
        $participants = array_map(fn (int $seed): Participant => $this->participant($seed, Uuid::v4()->toString()), [1, 2, 3, 4]);

        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->exactly(2))->method('schedule')->willReturnCallback(
            static fn (string $one, string $two, string $game, int $teamSize, ?string $tournament): Fight => Fight::create(
                new FightId(Uuid::v4()->toString()),
                new CompetitorId($one),
                new CompetitorId($two),
                new GameId($game),
                new TeamSize($teamSize),
                new TournamentId((string) $tournament),
            )
        );

        $saved = [];
        $matchupRepository = $this->createStub(MatchupRepositoryInterface::class);
        $matchupRepository->method('save')->willReturnCallback(
            static function (Matchup $matchup) use (&$saved): void {
                $saved[$matchup->getId()->getValue()] = $matchup;
            }
        );

        $view = json_decode($this->startHandler($tournament, $participants, $matchupRepository, $scheduler, self::ORGANIZER)(
            new StartTournamentCommand(self::TOURNAMENT_ID)
        ), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(TournamentStatus::ONGOING, $tournament->getStatus());
        $this->assertCount(3, $saved);
        $this->assertSame(2, $view['rounds']);
        $this->assertNotNull($view['matchups'][0]['fight']);
        $this->assertNotNull($view['matchups'][1]['fight']);
        $this->assertNull($view['matchups'][2]['fight']);
    }

    public function test_only_the_organizer_starts_the_tournament(): void
    {
        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->never())->method('schedule');

        $this->expectException(PermissionDeniedException::class);

        $this->startHandler($this->tournament(1), [], $this->createStub(MatchupRepositoryInterface::class), $scheduler, self::MY_USER)(
            new StartTournamentCommand(self::TOURNAMENT_ID)
        );
    }

    public function test_a_settled_fight_moves_its_winner_on_and_opens_the_next_fight(): void
    {
        $tournament = $this->tournament(1);
        $participants = array_map(fn (int $seed): Participant => $this->participant($seed, Uuid::v4()->toString()), [1, 2, 3, 4]);
        $bracket = Tournament::start($tournament, $participants, $this->ids(3));
        Tournament::attachFight($tournament, $bracket[0], new FightId(self::FIGHT_ID));
        Tournament::recordWinner($tournament, $bracket, $bracket[1], $participants[1]->getCompetitor());
        $tournament->pullDomainEvents();

        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->once())
            ->method('schedule')
            ->with($participants[0]->getCompetitor()->getValue(), $participants[1]->getCompetitor()->getValue(), self::GAME_ID, 1, self::TOURNAMENT_ID)
            ->willReturn(Fight::create(
                new FightId(Uuid::v4()->toString()),
                $participants[0]->getCompetitor(),
                $participants[1]->getCompetitor(),
                new GameId(self::GAME_ID),
                new TeamSize(1),
            ));

        $this->advanceHandler($tournament, $bracket, $scheduler)(
            new AdvanceTournamentCommand(self::FIGHT_ID, $participants[0]->getCompetitor()->getValue())
        );

        $this->assertSame($participants[0]->getCompetitor()->getValue(), $bracket[0]->getWinner()?->getValue());
        $this->assertNotNull($bracket[2]->getFight());
    }

    public function test_the_final_decides_the_tournament(): void
    {
        $tournament = $this->tournament(1);
        $participants = array_map(fn (int $seed): Participant => $this->participant($seed, Uuid::v4()->toString()), [1, 2]);
        $bracket = Tournament::start($tournament, $participants, $this->ids(1));
        Tournament::attachFight($tournament, $bracket[0], new FightId(self::FIGHT_ID));

        $scheduler = $this->createMock(FightSchedulerInterface::class);
        $scheduler->expects($this->never())->method('schedule');

        $this->advanceHandler($tournament, $bracket, $scheduler)(
            new AdvanceTournamentCommand(self::FIGHT_ID, $participants[1]->getCompetitor()->getValue())
        );

        $this->assertSame(TournamentStatus::FINISHED, $tournament->getStatus());
        $this->assertSame($participants[1]->getCompetitor()->getValue(), $tournament->getWinner()?->getValue());
    }

    public function test_only_a_tournament_fight_moves_a_bracket(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(AdvanceTournamentCommand::class))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $subscriber = new FightSettledEventSubscriber($messageBus);
        $winner = new CompetitorId(Uuid::v4()->toString());

        $subscriber->advanceTournament(new FightSettledEvent(new FightId(self::FIGHT_ID), $winner, null));
        $subscriber->advanceTournament(new FightSettledEvent(new FightId(self::FIGHT_ID), $winner, new TournamentId(self::TOURNAMENT_ID)));
    }

    private function tournament(int $teamSize, int $capacity = 8): Tournament
    {
        $tournament = Tournament::create(
            new TournamentId(self::TOURNAMENT_ID),
            new TournamentName('Autumn Cup'),
            new GameId(self::GAME_ID),
            new TeamSize($teamSize),
            $capacity,
            new OrganizerId(self::ORGANIZER),
            new \DateTimeImmutable('+1 week'),
        );
        $tournament->pullDomainEvents();

        return $tournament;
    }

    private function participant(int $seed, string $competitor): Participant
    {
        $participant = new Participant(new ParticipantId(Uuid::v4()->toString()));
        $participant->setTournament(new TournamentId(self::TOURNAMENT_ID));
        $participant->setCompetitor(new CompetitorId(str_starts_with($competitor, 'competitor-of-') ? self::uuidFor($competitor) : $competitor));
        $participant->setSeed($seed);
        $participant->setCreatedAt(new \DateTimeImmutable('now'));
        $participant->setUpdatedAt(new \DateTimeImmutable('now'));

        return $participant;
    }

    /**
     * Competitor ids in these tests read "competitor-of-<reference>"; they are
     * mapped to stable UUIDs so that value objects accept them.
     */
    private static function uuidFor(string $name): string
    {
        $hash = md5($name);

        return \sprintf('%s-%s-4%s-8%s-%s', substr($hash, 0, 8), substr($hash, 8, 4), substr($hash, 13, 3), substr($hash, 17, 3), substr($hash, 20, 12));
    }

    /**
     * @return list<MatchupId>
     */
    private function ids(int $count): array
    {
        return array_map(static fn (): MatchupId => new MatchupId(Uuid::v4()->toString()), range(1, $count));
    }

    private function registry(): CompetitorRegistryInterface
    {
        $registry = $this->createStub(CompetitorRegistryInterface::class);
        $this->configureRegistry($registry);

        return $registry;
    }

    private function configureRegistry(CompetitorRegistryInterface $registry): void
    {
        $registry->method('enlistPlayer')->willReturnCallback(static fn (string $id): string => self::uuidFor('competitor-of-'.$id));
        $registry->method('enlistTeam')->willReturnCallback(static fn (string $id): string => self::uuidFor('competitor-of-'.$id));
        $registry->method('describe')->willReturnCallback(static function (array $ids): array {
            $described = [];
            foreach ([self::MY_TEAM, self::THEIR_TEAM] as $team) {
                if (\in_array(self::uuidFor('competitor-of-'.$team), $ids, true)) {
                    $described[self::uuidFor('competitor-of-'.$team)] = ['type' => 'team', 'reference' => $team, 'name' => null];
                }
            }

            return $described;
        });
        $registry->method('representedBy')->willReturnCallback(static fn (string $user): array => match ($user) {
            self::MY_USER => [self::uuidFor('competitor-of-'.self::MY_PLAYER)],
            self::THEIR_USER => [self::uuidFor('competitor-of-'.self::THEIR_PLAYER)],
            default => [],
        });
    }

    private function createHandler(TournamentRepositoryInterface $tournamentRepository): CreateTournamentHandler
    {
        return new CreateTournamentHandler(
            $tournamentRepository,
            $this->repositoryStub(GameRepositoryInterface::class, [self::aGame(self::GAME_ID, [1, 2])]),
            $this->signedIn(self::ORGANIZER),
            $this->createStub(EventDispatcherInterface::class),
        );
    }

    private function registerHandler(
        Tournament $tournament,
        ParticipantRepositoryInterface $participantRepository,
        string $caller,
        ?CompetitorRegistryInterface $registry = null,
    ): RegisterParticipantHandler {
        $myClan = self::aClan(self::MY_CLAN, self::GAME_ID, self::MY_PLAYER);
        [$myTeam, $myLineup] = self::aTeam(self::MY_TEAM, $myClan, [self::MY_PLAYER, self::MATE_PLAYER]);
        [$theirTeam, $theirLineup] = self::aTeam(self::THEIR_TEAM, self::aClan(self::MY_CLAN, self::GAME_ID, self::THEIR_PLAYER), [self::THEIR_PLAYER, self::MATE_PLAYER]);

        if (null !== $registry) {
            $this->configureRegistry($registry);
        }

        return new RegisterParticipantHandler(
            $this->repositoryStub(TournamentRepositoryInterface::class, [$tournament]),
            $participantRepository,
            $this->repositoryStub(PlayerRepositoryInterface::class, [
                self::aPlayer(self::MY_PLAYER, self::MY_USER, self::GAME_ID),
                self::aPlayer(self::THEIR_PLAYER, self::THEIR_USER, self::GAME_ID),
            ]),
            $this->repositoryStub(TeamRepositoryInterface::class, [$myTeam, $theirTeam]),
            $this->repositoryStub(TeamPlayerRepositoryInterface::class, array_merge($myLineup, $theirLineup)),
            $registry ?? $this->registry(),
            $this->signedIn($caller),
            $this->createStub(EventDispatcherInterface::class),
        );
    }

    private function withdrawHandler(ParticipantRepositoryInterface $participantRepository, string $caller): WithdrawParticipantHandler
    {
        return new WithdrawParticipantHandler(
            $this->repositoryStub(TournamentRepositoryInterface::class, [$this->tournament(1)]),
            $participantRepository,
            $this->registry(),
            $this->signedIn($caller),
            $this->createStub(EventDispatcherInterface::class),
        );
    }

    /**
     * @param list<Participant> $participants
     */
    private function startHandler(
        Tournament $tournament,
        array $participants,
        MatchupRepositoryInterface $matchupRepository,
        FightSchedulerInterface $scheduler,
        string $caller,
    ): StartTournamentHandler {
        return new StartTournamentHandler(
            $this->repositoryStub(TournamentRepositoryInterface::class, [$tournament]),
            $this->repositoryStub(ParticipantRepositoryInterface::class, $participants),
            $matchupRepository,
            $scheduler,
            $this->registry(),
            $this->signedIn($caller),
            $this->createStub(EventDispatcherInterface::class),
        );
    }

    /**
     * @param list<Matchup> $bracket
     */
    private function advanceHandler(Tournament $tournament, array $bracket, FightSchedulerInterface $scheduler): AdvanceTournamentHandler
    {
        return new AdvanceTournamentHandler(
            $this->repositoryStub(TournamentRepositoryInterface::class, [$tournament]),
            $this->repositoryStub(MatchupRepositoryInterface::class, $bracket),
            $scheduler,
            $this->createStub(EventDispatcherInterface::class),
        );
    }
}
