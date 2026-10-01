<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Clan\Application;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Application\Model\DissolveClanCommand;
use App\Competition\Profile\Clan\Application\Service\DissolveClanHandler;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayer;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DissolveClanHandlerTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const CLAN_ID = '22222222-2222-4222-8222-222222222222';
    private const LEADER_USER = '33333333-3333-4333-8333-333333333333';
    private const LEADER_PLAYER = '34343434-3434-4343-8343-343434343434';
    private const MEMBER_USER = '44444444-4444-4444-8444-444444444444';
    private const MEMBER_PLAYER = '45454545-4545-4545-8545-454545454545';
    private const INVITED_PLAYER = '46464646-4646-4646-8646-464646464646';
    private const ASKING_PLAYER = '47474747-4747-4747-8747-474747474747';
    private const NEW_TEAM = '55555555-5555-4555-8555-555555555555';
    private const PLAYED_TEAM = '56565656-5656-4565-8565-565656565656';

    public function test_the_leader_dissolves_the_clan_and_only_the_teams_that_never_played_go(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);
        $places = [
            self::leadership($clan),
            self::membership($clan, self::MEMBER_PLAYER),
            self::invitation($clan, self::INVITED_PLAYER),
            self::joinRequest($clan, self::ASKING_PLAYER),
        ];
        [$newTeam, $newLineup] = self::aTeam(self::NEW_TEAM, $clan, [self::LEADER_PLAYER, self::MEMBER_PLAYER], 'Rookies');
        [$playedTeam, $playedLineup] = self::aTeam(self::PLAYED_TEAM, $clan, [self::LEADER_PLAYER, self::MEMBER_PLAYER], 'Veterans');

        $removedPlaces = [];
        $clanMemberRepository = $this->repositoryStub(ClanMemberRepositoryInterface::class, $places);
        $clanMemberRepository->method('remove')->willReturnCallback(
            static function (ClanMember $place) use (&$removedPlaces): void {
                $removedPlaces[] = $place;
            }
        );

        $removedTeams = [];
        $teamRepository = $this->repositoryStub(TeamRepositoryInterface::class, [$newTeam, $playedTeam]);
        $teamRepository->method('remove')->willReturnCallback(
            static function (Team $team) use (&$removedTeams): void {
                $removedTeams[] = $team->getId()->getValue();
            }
        );

        $removedLineup = [];
        $teamPlayerRepository = $this->repositoryStub(TeamPlayerRepositoryInterface::class, [...$newLineup, ...$playedLineup]);
        $teamPlayerRepository->method('remove')->willReturnCallback(
            static function (TeamPlayer $teamPlayer) use (&$removedLineup): void {
                $removedLineup[] = $teamPlayer->getTeam()->getValue();
            }
        );

        $clanRepository = $this->repositoryMock(ClanRepositoryInterface::class, [$clan]);
        $clanRepository->expects($this->once())->method('save')->with($clan);

        $this->handler($clanRepository, $clanMemberRepository, $teamRepository, $teamPlayerRepository, $this->signedIn(self::LEADER_USER))(
            new DissolveClanCommand(self::CLAN_ID, 'Seed1234!'),
        );

        $this->assertTrue($clan->isDissolved());
        $this->assertSame($places, $removedPlaces);
        $this->assertSame([self::NEW_TEAM], $removedTeams);
        $this->assertSame([self::NEW_TEAM, self::NEW_TEAM], $removedLineup);
    }

    public function test_only_the_leader_dissolves_the_clan(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);

        $clanRepository = $this->repositoryMock(ClanRepositoryInterface::class, [$clan]);
        $clanRepository->expects($this->never())->method('save');

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessage('only the clan leader dissolves the clan');

        $this->handler($clanRepository, $this->untouchedPlaces(), currentUserProvider: $this->signedIn(self::MEMBER_USER))(
            new DissolveClanCommand(self::CLAN_ID, 'Seed1234!'),
        );
    }

    public function test_a_wrong_password_dissolves_nothing(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);

        $clanRepository = $this->repositoryMock(ClanRepositoryInterface::class, [$clan]);
        $clanRepository->expects($this->never())->method('save');

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn(new User(self::LEADER_USER));
        $currentUserProvider->method('confirmPassword')->willThrowException(new PermissionDeniedException('the password does not match'));

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessage('the password does not match');

        $this->handler($clanRepository, $this->untouchedPlaces(), currentUserProvider: $currentUserProvider)(
            new DissolveClanCommand(self::CLAN_ID, 'nope'),
        );
    }

    public function test_a_clan_already_dissolved_is_not_found(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);
        Clan::dissolve($clan);

        $this->expectException(NotFoundException::class);

        $this->handler($this->repositoryStub(ClanRepositoryInterface::class, [$clan]), $this->untouchedPlaces(), currentUserProvider: $this->signedIn(self::LEADER_USER))(
            new DissolveClanCommand(self::CLAN_ID, 'Seed1234!'),
        );
    }

    private function untouchedPlaces(): ClanMemberRepositoryInterface
    {
        $clanMemberRepository = $this->createMock(ClanMemberRepositoryInterface::class);
        $clanMemberRepository->expects($this->never())->method('remove');

        return $clanMemberRepository;
    }

    private function handler(
        ClanRepositoryInterface $clanRepository,
        ClanMemberRepositoryInterface $clanMemberRepository,
        ?TeamRepositoryInterface $teamRepository = null,
        ?TeamPlayerRepositoryInterface $teamPlayerRepository = null,
        ?CurrentUserProviderInterface $currentUserProvider = null,
    ): DissolveClanHandler {
        $registry = $this->createStub(CompetitorRegistryProviderInterface::class);
        $registry->method('teamHasCompeted')->willReturnCallback(static fn (string $teamId): bool => self::PLAYED_TEAM === $teamId);

        return new DissolveClanHandler(
            $clanRepository,
            $clanMemberRepository,
            $this->repositoryStub(PlayerRepositoryInterface::class, [
                self::aPlayer(self::LEADER_PLAYER, self::LEADER_USER, self::GAME_ID, 'Leader#0001'),
                self::aPlayer(self::MEMBER_PLAYER, self::MEMBER_USER, self::GAME_ID, 'Member#0002'),
            ]),
            $teamRepository ?? $this->repositoryStub(TeamRepositoryInterface::class, []),
            $teamPlayerRepository ?? $this->repositoryStub(TeamPlayerRepositoryInterface::class, []),
            $registry,
            $currentUserProvider ?? $this->signedIn(self::LEADER_USER),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(NormalizerInterface::class),
        );
    }
}
