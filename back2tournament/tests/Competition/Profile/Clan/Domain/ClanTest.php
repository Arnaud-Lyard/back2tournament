<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Clan\Domain;

use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Entity\ClanMemberId;
use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Enum\ClanRole;
use App\Competition\Profile\Clan\Domain\Event\ClanDissolvedEvent;
use App\Competition\Profile\Clan\Domain\Event\ClanMemberRequestedEvent;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\ClanNameValueObject;
use App\Shared\ValueObject\ClanTagValueObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClanTest extends TestCase
{
    private const CLAN_ID = '11111111-1111-4111-8111-111111111111';
    private const OTHER_CLAN_ID = '12121212-1212-4121-8121-121212121212';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const LEADER_ID = '33333333-3333-4333-8333-333333333333';
    private const PLAYER_ID = '44444444-4444-4444-8444-444444444444';
    private const LEADER_MEMBERSHIP_ID = '55555555-5555-4555-8555-555555555555';
    private const MEMBERSHIP_ID = '66666666-6666-4666-8666-666666666666';

    public function test_the_founder_leads_the_clan_and_is_its_first_member(): void
    {
        $clan = $this->clan();

        $membership = Clan::createLeaderMembership($clan, new ClanMemberId(self::LEADER_MEMBERSHIP_ID));

        $this->assertSame(self::LEADER_ID, $clan->getLeader()->getValue());
        $this->assertSame(self::LEADER_ID, $membership->getPlayer()->getValue());
        $this->assertSame(ClanRole::LEADER, $membership->getRole());
        $this->assertSame(ClanMemberStatus::ACTIVE, $membership->getStatus());
    }

    public function test_a_tag_is_stored_upper_cased(): void
    {
        $this->assertSame('B2T', new ClanTagValueObject(' b2t ')->getValue());
    }

    #[DataProvider('refusedTags')]
    public function test_a_tag_holds_two_to_five_letters_or_digits(string $tag): void
    {
        $this->expectException(ValidationException::class);

        new ClanTagValueObject($tag);
    }

    public static function refusedTags(): iterable
    {
        yield 'one character' => ['X'];
        yield 'six characters' => ['ABCDEF'];
        yield 'punctuation' => ['B-2'];
    }

    public function test_a_clan_name_is_never_blank(): void
    {
        $this->expectException(ValidationException::class);

        new ClanNameValueObject('   ');
    }

    public function test_an_invited_player_waits_for_accepting(): void
    {
        $membership = Clan::invite($this->clan(), new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));

        $this->assertSame(ClanMemberStatus::INVITED, $membership->getStatus());
        $this->assertSame(ClanRole::MEMBER, $membership->getRole());
    }

    public function test_the_leader_is_never_invited_to_their_own_clan(): void
    {
        $this->expectException(ConflictException::class);

        Clan::invite($this->clan(), new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::LEADER_ID));
    }

    public function test_accepting_the_invitation_makes_a_member(): void
    {
        $clan = $this->clan();
        $membership = Clan::invite($clan, new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));

        Clan::join($clan, $membership);

        $this->assertSame(ClanMemberStatus::ACTIVE, $membership->getStatus());
    }

    public function test_a_member_does_not_join_twice(): void
    {
        $clan = $this->clan();
        $membership = Clan::invite($clan, new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));
        Clan::join($clan, $membership);

        $this->expectException(ConflictException::class);

        Clan::join($clan, $membership);
    }

    public function test_an_invitation_of_another_clan_is_not_accepted_here(): void
    {
        $other = Clan::create(
            new ClanId(self::OTHER_CLAN_ID),
            new ClanNameValueObject('Others'),
            new ClanTagValueObject('OTH'),
            new GameId(self::GAME_ID),
            new PlayerId(self::LEADER_ID),
        );
        $membership = Clan::invite($other, new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));

        $this->expectException(NotFoundException::class);

        Clan::join($this->clan(), $membership);
    }

    public function test_a_player_asks_to_join_and_waits_for_the_leader(): void
    {
        $clan = $this->clan();
        $clan->pullDomainEvents();

        $membership = Clan::request($clan, new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));

        $this->assertSame(ClanMemberStatus::REQUESTED, $membership->getStatus());
        $this->assertSame(ClanRole::MEMBER, $membership->getRole());
        $this->assertInstanceOf(ClanMemberRequestedEvent::class, $clan->pullDomainEvents()[0]);
    }

    public function test_the_leader_never_asks_to_join_their_own_clan(): void
    {
        $this->expectException(ConflictException::class);

        Clan::request($this->clan(), new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::LEADER_ID));
    }

    public function test_the_leader_admits_a_player_who_asked(): void
    {
        $clan = $this->clan();
        $membership = Clan::request($clan, new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));

        Clan::admit($clan, $membership);

        $this->assertSame(ClanMemberStatus::ACTIVE, $membership->getStatus());
    }

    public function test_a_player_who_asked_does_not_let_themselves_in(): void
    {
        $clan = $this->clan();
        $membership = Clan::request($clan, new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('not accepted this request yet');

        Clan::join($clan, $membership);
    }

    public function test_the_leader_does_not_accept_an_invitation_for_the_player(): void
    {
        $clan = $this->clan();
        $membership = Clan::invite($clan, new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('accept the invitation themselves');

        Clan::admit($clan, $membership);
    }

    public function test_a_member_is_not_admitted_twice(): void
    {
        $clan = $this->clan();
        $membership = Clan::request($clan, new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));
        Clan::admit($clan, $membership);

        $this->expectException(ConflictException::class);

        Clan::admit($clan, $membership);
    }

    public function test_a_request_to_another_clan_is_not_accepted_here(): void
    {
        $other = Clan::create(
            new ClanId(self::OTHER_CLAN_ID),
            new ClanNameValueObject('Others'),
            new ClanTagValueObject('OTH'),
            new GameId(self::GAME_ID),
            new PlayerId(self::LEADER_ID),
        );
        $membership = Clan::request($other, new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));

        $this->expectException(NotFoundException::class);

        Clan::admit($this->clan(), $membership);
    }

    public function test_the_leader_never_leaves(): void
    {
        $clan = $this->clan();
        $leadership = Clan::createLeaderMembership($clan, new ClanMemberId(self::LEADER_MEMBERSHIP_ID));

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('leader cannot leave');

        Clan::remove($clan, $leadership);
    }

    public function test_a_member_leaves_and_it_is_announced(): void
    {
        $clan = $this->clan();
        $membership = Clan::invite($clan, new ClanMemberId(self::MEMBERSHIP_ID), new PlayerId(self::PLAYER_ID));
        Clan::join($clan, $membership);
        $clan->pullDomainEvents();

        Clan::remove($clan, $membership);

        $this->assertCount(1, $clan->pullDomainEvents());
    }

    public function test_a_dissolved_clan_says_when_it_was_dissolved(): void
    {
        $clan = $this->clan();
        $clan->pullDomainEvents();

        $this->assertFalse($clan->isDissolved());

        Clan::dissolve($clan);

        $this->assertTrue($clan->isDissolved());
        $this->assertNotNull($clan->getDissolvedAt());
        $this->assertSame([ClanDissolvedEvent::class], array_map(static fn (object $event): string => $event::class, $clan->pullDomainEvents()));
    }

    public function test_a_clan_is_dissolved_once(): void
    {
        $clan = $this->clan();
        Clan::dissolve($clan);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('this clan is already dissolved');

        Clan::dissolve($clan);
    }

    private function clan(): Clan
    {
        return Clan::create(
            new ClanId(self::CLAN_ID),
            new ClanNameValueObject('Back to Tournament'),
            new ClanTagValueObject('B2T'),
            new GameId(self::GAME_ID),
            new PlayerId(self::LEADER_ID),
        );
    }
}
