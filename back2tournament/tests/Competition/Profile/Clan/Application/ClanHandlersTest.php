<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Clan\Application;

use App\Competition\Profile\Clan\Application\Model\CreateClanCommand;
use App\Competition\Profile\Clan\Application\Model\InviteClanMemberCommand;
use App\Competition\Profile\Clan\Application\Model\JoinClanCommand;
use App\Competition\Profile\Clan\Application\Model\RemoveClanMemberCommand;
use App\Competition\Profile\Clan\Application\Service\CreateClanHandler;
use App\Competition\Profile\Clan\Application\Service\InviteClanMemberHandler;
use App\Competition\Profile\Clan\Application\Service\JoinClanHandler;
use App\Competition\Profile\Clan\Application\Service\RemoveClanMemberHandler;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Enum\ClanRole;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ClanHandlersTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const OTHER_GAME_ID = '12121212-1212-4121-8121-121212121212';
    private const CLAN_ID = '22222222-2222-4222-8222-222222222222';
    private const OTHER_CLAN_ID = '23232323-2323-4232-8232-232323232323';
    private const LEADER_USER = '33333333-3333-4333-8333-333333333333';
    private const LEADER_PLAYER = '34343434-3434-4343-8343-343434343434';
    private const MEMBER_USER = '44444444-4444-4444-8444-444444444444';
    private const MEMBER_PLAYER = '45454545-4545-4545-8545-454545454545';
    private const STRANGER_USER = '55555555-5555-4555-8555-555555555555';
    private const TEAM_ID = '66666666-6666-4666-8666-666666666666';

    public function test_founding_a_clan_saves_it_with_its_leader_as_first_member(): void
    {
        $savedMemberships = [];

        $clanRepository = $this->repositoryMock(ClanRepositoryInterface::class, []);
        $clanRepository->expects($this->once())->method('save')->with($this->isInstanceOf(Clan::class));

        $clanMemberRepository = $this->repositoryStub(ClanMemberRepositoryInterface::class, []);
        $clanMemberRepository->method('save')->willReturnCallback(
            static function (ClanMember $membership) use (&$savedMemberships): void {
                $savedMemberships[] = $membership;
            }
        );

        $this->createHandler($clanRepository, $clanMemberRepository)(new CreateClanCommand(self::GAME_ID, 'Back to Tournament', 'b2t'));

        $this->assertCount(1, $savedMemberships);
        $this->assertSame(ClanRole::LEADER, $savedMemberships[0]->getRole());
        $this->assertSame(self::LEADER_PLAYER, $savedMemberships[0]->getPlayer()->getValue());
    }

    public function test_a_user_without_a_profile_in_the_game_founds_nothing(): void
    {
        $clanRepository = $this->repositoryMock(ClanRepositoryInterface::class, []);
        $clanRepository->expects($this->never())->method('save');

        $this->expectException(NotFoundException::class);

        $this->createHandler($clanRepository, $this->repositoryStub(ClanMemberRepositoryInterface::class, []))(
            new CreateClanCommand(self::OTHER_GAME_ID, 'Back to Tournament', 'B2T')
        );
    }

    public function test_a_member_of_a_clan_founds_no_second_one(): void
    {
        $other = self::aClan(self::OTHER_CLAN_ID, self::GAME_ID, self::MEMBER_PLAYER, 'OTH');

        $clanRepository = $this->repositoryMock(ClanRepositoryInterface::class, [$other]);
        $clanRepository->expects($this->never())->method('save');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('already belong to a clan');

        $this->createHandler($clanRepository, $this->repositoryStub(ClanMemberRepositoryInterface::class, [
            self::membership($other, self::LEADER_PLAYER),
        ]))(new CreateClanCommand(self::GAME_ID, 'Back to Tournament', 'B2T'));
    }

    public function test_a_tag_is_unique_within_the_game(): void
    {
        $clanRepository = $this->repositoryMock(ClanRepositoryInterface::class, [
            self::aClan(self::OTHER_CLAN_ID, self::GAME_ID, self::MEMBER_PLAYER, 'B2T'),
        ]);
        $clanRepository->expects($this->never())->method('save');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('already taken');

        $this->createHandler($clanRepository, $this->repositoryStub(ClanMemberRepositoryInterface::class, []))(
            new CreateClanCommand(self::GAME_ID, 'Back to Tournament', 'b2t')
        );
    }

    public function test_the_leader_invites_a_player_of_the_game(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, [self::leadership($clan)]);
        $clanMemberRepository->expects($this->once())->method('save')->with($this->callback(
            static fn (ClanMember $membership): bool => ClanMemberStatus::INVITED === $membership->getStatus()
                && self::MEMBER_PLAYER === $membership->getPlayer()->getValue()
        ));

        $this->inviteHandler($clan, $clanMemberRepository, self::LEADER_USER)(new InviteClanMemberCommand(self::CLAN_ID, self::MEMBER_PLAYER));
    }

    public function test_only_the_leader_invites(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, []);
        $clanMemberRepository->expects($this->never())->method('save');

        $this->expectException(PermissionDeniedException::class);

        $this->inviteHandler($clan, $clanMemberRepository, self::MEMBER_USER)(new InviteClanMemberCommand(self::CLAN_ID, self::MEMBER_PLAYER));
    }

    public function test_a_player_of_another_game_is_not_invited(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::OTHER_GAME_ID, self::LEADER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, []);
        $clanMemberRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);

        $this->inviteHandler($clan, $clanMemberRepository, self::LEADER_USER)(new InviteClanMemberCommand(self::CLAN_ID, self::MEMBER_PLAYER));
    }

    public function test_a_player_is_invited_once(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, [self::invitation($clan, self::MEMBER_PLAYER)]);
        $clanMemberRepository->expects($this->never())->method('save');

        $this->expectException(ConflictException::class);

        $this->inviteHandler($clan, $clanMemberRepository, self::LEADER_USER)(new InviteClanMemberCommand(self::CLAN_ID, self::MEMBER_PLAYER));
    }

    public function test_the_invited_player_accepts_and_becomes_a_member(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);
        $invitation = self::invitation($clan, self::MEMBER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, [$invitation]);
        $clanMemberRepository->expects($this->once())->method('save');

        $this->joinHandler($clan, $clanMemberRepository, self::MEMBER_USER)(new JoinClanCommand(self::CLAN_ID));

        $this->assertSame(ClanMemberStatus::ACTIVE, $invitation->getStatus());
    }

    public function test_nobody_joins_without_an_invitation(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, []);
        $clanMemberRepository->expects($this->never())->method('save');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageIsOrContains('not been invited');

        $this->joinHandler($clan, $clanMemberRepository, self::MEMBER_USER)(new JoinClanCommand(self::CLAN_ID));
    }

    public function test_a_member_of_another_clan_leaves_it_before_joining(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);
        $other = self::aClan(self::OTHER_CLAN_ID, self::GAME_ID, self::STRANGER_USER, 'OTH');

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, [
            self::membership($other, self::MEMBER_PLAYER),
            self::invitation($clan, self::MEMBER_PLAYER),
        ]);
        $clanMemberRepository->expects($this->never())->method('save');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('leave your current clan');

        $this->joinHandler($clan, $clanMemberRepository, self::MEMBER_USER)(new JoinClanCommand(self::CLAN_ID));
    }

    public function test_a_member_leaves_on_their_own(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);
        $membership = self::membership($clan, self::MEMBER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, [$membership]);
        $clanMemberRepository->expects($this->once())->method('remove')->with($this->identicalTo($membership));

        $this->removeHandler($clan, $clanMemberRepository, self::MEMBER_USER)(new RemoveClanMemberCommand(self::CLAN_ID, self::MEMBER_PLAYER));
    }

    public function test_the_leader_lets_a_member_go(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);
        $membership = self::membership($clan, self::MEMBER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, [$membership]);
        $clanMemberRepository->expects($this->once())->method('remove');

        $this->removeHandler($clan, $clanMemberRepository, self::LEADER_USER)(new RemoveClanMemberCommand(self::CLAN_ID, self::MEMBER_PLAYER));
    }

    public function test_a_stranger_ends_no_membership(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, [self::membership($clan, self::MEMBER_PLAYER)]);
        $clanMemberRepository->expects($this->never())->method('remove');

        $this->expectException(PermissionDeniedException::class);

        $this->removeHandler($clan, $clanMemberRepository, self::STRANGER_USER)(new RemoveClanMemberCommand(self::CLAN_ID, self::MEMBER_PLAYER));
    }

    public function test_the_leader_does_not_leave(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, [self::leadership($clan)]);
        $clanMemberRepository->expects($this->never())->method('remove');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('leader cannot leave');

        $this->removeHandler($clan, $clanMemberRepository, self::LEADER_USER)(new RemoveClanMemberCommand(self::CLAN_ID, self::LEADER_PLAYER));
    }

    public function test_a_member_playing_in_a_team_of_the_clan_stays(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER_PLAYER);

        $clanMemberRepository = $this->repositoryMock(ClanMemberRepositoryInterface::class, [self::membership($clan, self::MEMBER_PLAYER)]);
        $clanMemberRepository->expects($this->never())->method('remove');

        [$team, $lineup] = self::aTeam(self::TEAM_ID, $clan, [self::LEADER_PLAYER, self::MEMBER_PLAYER]);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('disband that team first');

        $this->removeHandler($clan, $clanMemberRepository, self::MEMBER_USER, [$team], $lineup)(
            new RemoveClanMemberCommand(self::CLAN_ID, self::MEMBER_PLAYER)
        );
    }

    private function players(): PlayerRepositoryInterface
    {
        return $this->repositoryStub(PlayerRepositoryInterface::class, [
            self::aPlayer(self::LEADER_PLAYER, self::LEADER_USER, self::GAME_ID, 'Leader#0001'),
            self::aPlayer(self::MEMBER_PLAYER, self::MEMBER_USER, self::GAME_ID, 'Member#0002'),
        ]);
    }

    private function createHandler(ClanRepositoryInterface $clanRepository, ClanMemberRepositoryInterface $clanMemberRepository): CreateClanHandler
    {
        return new CreateClanHandler(
            $clanRepository,
            $clanMemberRepository,
            $this->players(),
            $this->signedIn(self::LEADER_USER),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
        );
    }

    private function inviteHandler(Clan $clan, ClanMemberRepositoryInterface $clanMemberRepository, string $caller): InviteClanMemberHandler
    {
        return new InviteClanMemberHandler(
            $this->repositoryStub(ClanRepositoryInterface::class, [$clan]),
            $clanMemberRepository,
            $this->players(),
            $this->signedIn($caller),
            $this->createStub(EventDispatcherInterface::class),
        );
    }

    private function joinHandler(Clan $clan, ClanMemberRepositoryInterface $clanMemberRepository, string $caller): JoinClanHandler
    {
        return new JoinClanHandler(
            $this->repositoryStub(ClanRepositoryInterface::class, [$clan]),
            $clanMemberRepository,
            $this->players(),
            $this->signedIn($caller),
            $this->createStub(EventDispatcherInterface::class),
        );
    }

    private function removeHandler(
        Clan $clan,
        ClanMemberRepositoryInterface $clanMemberRepository,
        string $caller,
        array $teams = [],
        array $teamPlayers = [],
    ): RemoveClanMemberHandler {
        return new RemoveClanMemberHandler(
            $this->repositoryStub(ClanRepositoryInterface::class, [$clan]),
            $clanMemberRepository,
            $this->players(),
            $this->repositoryStub(TeamRepositoryInterface::class, $teams),
            $this->repositoryStub(TeamPlayerRepositoryInterface::class, $teamPlayers),
            $this->signedIn($caller),
            $this->createStub(EventDispatcherInterface::class),
        );
    }
}
