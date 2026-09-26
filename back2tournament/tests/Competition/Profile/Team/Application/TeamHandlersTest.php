<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Team\Application;

use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Application\Model\CreateTeamCommand;
use App\Competition\Profile\Team\Application\Model\DisbandTeamCommand;
use App\Competition\Profile\Team\Application\Service\CreateTeamHandler;
use App\Competition\Profile\Team\Application\Service\DisbandTeamHandler;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TeamHandlersTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const CLAN_ID = '22222222-2222-4222-8222-222222222222';
    private const TEAM_ID = '33333333-3333-4333-8333-333333333333';
    private const LEADER_USER = '44444444-4444-4444-8444-444444444444';
    private const LEADER_PLAYER = '45454545-4545-4545-8545-454545454545';
    private const MEMBER_USER = '55555555-5555-4555-8555-555555555555';
    private const MEMBER_PLAYER = '56565656-5656-4565-8565-565656565656';
    private const INVITED_PLAYER = '67676767-6767-4676-8676-676767676767';

    public function test_the_clan_leader_composes_a_two_versus_two_team(): void
    {
        $lineup = [];

        $teamRepository = $this->createMock(TeamRepositoryInterface::class);
        $teamRepository->expects($this->once())->method('save')->with($this->isInstanceOf(Team::class));

        $teamPlayerRepository = $this->createStub(TeamPlayerRepositoryInterface::class);
        $teamPlayerRepository->method('save')->willReturnCallback(
            static function ($teamPlayer) use (&$lineup): void {
                $lineup[] = $teamPlayer->getPlayer()->getValue();
            }
        );

        $view = json_decode(
            $this->createHandler($teamRepository, $teamPlayerRepository, self::LEADER_USER)($this->command(2, [self::LEADER_PLAYER, self::MEMBER_PLAYER])),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame([self::LEADER_PLAYER, self::MEMBER_PLAYER], $lineup);
        $this->assertSame(2, $view['size']);
        $this->assertSame(self::LEADER_PLAYER, $view['players'][0]['id']['value']);
    }

    public function test_only_the_clan_leader_composes_teams(): void
    {
        $teamRepository = $this->createMock(TeamRepositoryInterface::class);
        $teamRepository->expects($this->never())->method('save');

        $this->expectException(PermissionDeniedException::class);

        $this->createHandler($teamRepository, $this->createStub(TeamPlayerRepositoryInterface::class), self::MEMBER_USER)(
            $this->command(2, [self::LEADER_PLAYER, self::MEMBER_PLAYER])
        );
    }

    public function test_a_format_the_game_is_not_played_in_is_refused(): void
    {
        $teamRepository = $this->createMock(TeamRepositoryInterface::class);
        $teamRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('is not played 5v5');

        $this->createHandler($teamRepository, $this->createStub(TeamPlayerRepositoryInterface::class), self::LEADER_USER)(
            $this->command(5, [self::LEADER_PLAYER, self::MEMBER_PLAYER])
        );
    }

    public function test_an_invited_player_is_not_yet_fielded(): void
    {
        $teamRepository = $this->createMock(TeamRepositoryInterface::class);
        $teamRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('active member');

        $this->createHandler($teamRepository, $this->createStub(TeamPlayerRepositoryInterface::class), self::LEADER_USER)(
            $this->command(2, [self::LEADER_PLAYER, self::INVITED_PLAYER])
        );
    }

    public function test_a_team_that_never_competed_is_disbanded_with_its_lineup(): void
    {
        [$team, $lineup] = self::aTeam(self::TEAM_ID, $this->clan(), [self::LEADER_PLAYER, self::MEMBER_PLAYER]);

        $teamRepository = $this->repositoryMock(TeamRepositoryInterface::class, [$team]);
        $teamRepository->expects($this->once())->method('remove')->with($this->identicalTo($team));

        $teamPlayerRepository = $this->repositoryMock(TeamPlayerRepositoryInterface::class, $lineup);
        $teamPlayerRepository->expects($this->exactly(2))->method('remove');

        $this->disbandHandler($teamRepository, $teamPlayerRepository, competed: false)(new DisbandTeamCommand(self::TEAM_ID));
    }

    public function test_a_team_that_competed_is_kept(): void
    {
        [$team, $lineup] = self::aTeam(self::TEAM_ID, $this->clan(), [self::LEADER_PLAYER, self::MEMBER_PLAYER]);

        $teamRepository = $this->repositoryMock(TeamRepositoryInterface::class, [$team]);
        $teamRepository->expects($this->never())->method('remove');

        $this->expectException(ConflictException::class);

        $this->disbandHandler($teamRepository, $this->repositoryStub(TeamPlayerRepositoryInterface::class, $lineup), competed: true)(
            new DisbandTeamCommand(self::TEAM_ID)
        );
    }

    private function clan(): Clan
    {
        return self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);
    }

    /**
     * @param list<string> $players
     */
    private function command(int $size, array $players): CreateTeamCommand
    {
        return new CreateTeamCommand(self::CLAN_ID, 'Falcons Duo', $size, $players, self::LEADER_PLAYER);
    }

    private function players(): PlayerRepositoryInterface
    {
        return $this->repositoryStub(PlayerRepositoryInterface::class, [
            self::aPlayer(self::LEADER_PLAYER, self::LEADER_USER, self::GAME_ID, 'Leader#0001'),
            self::aPlayer(self::MEMBER_PLAYER, self::MEMBER_USER, self::GAME_ID, 'Member#0002'),
        ]);
    }

    private function createHandler(
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        string $caller,
    ): CreateTeamHandler {
        $clan = $this->clan();

        return new CreateTeamHandler(
            $teamRepository,
            $teamPlayerRepository,
            $this->repositoryStub(ClanRepositoryInterface::class, [$clan]),
            $this->repositoryStub(ClanMemberRepositoryInterface::class, [
                self::leadership($clan),
                self::membership($clan, self::MEMBER_PLAYER),
                self::invitation($clan, self::INVITED_PLAYER),
            ]),
            $this->repositoryStub(GameRepositoryInterface::class, [self::aGame(self::GAME_ID, [1, 2, 3])]),
            $this->players(),
            $this->signedIn($caller),
            $this->createStub(EventDispatcherInterface::class),
        );
    }

    private function disbandHandler(
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        bool $competed,
    ): DisbandTeamHandler {
        $competitorRegistry = $this->createStub(CompetitorRegistryInterface::class);
        $competitorRegistry->method('teamHasCompeted')->willReturn($competed);

        return new DisbandTeamHandler(
            $teamRepository,
            $teamPlayerRepository,
            $this->repositoryStub(ClanRepositoryInterface::class, [$this->clan()]),
            $this->players(),
            $competitorRegistry,
            $this->signedIn(self::LEADER_USER),
            $this->createStub(EventDispatcherInterface::class),
        );
    }
}
