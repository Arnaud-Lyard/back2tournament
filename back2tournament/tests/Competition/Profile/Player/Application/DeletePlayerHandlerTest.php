<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Player\Application;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Player\Application\Model\DeletePlayerCommand;
use App\Competition\Profile\Player\Application\Service\DeletePlayerHandler;
use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Event\PlayerDeletedEvent;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorIdProviderInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeletePlayerHandlerTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const PLAYER_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const OWNER_ID = '33333333-3333-4333-8333-333333333333';
    private const STRANGER_ID = '44444444-4444-4444-8444-444444444444';
    private const CLAN_ID = '55555555-5555-4555-8555-555555555555';
    private const OTHER_CLAN_ID = '56565656-5656-4565-8565-565656565656';
    private const OTHER_LEADER_ID = '66666666-6666-4666-8666-666666666666';

    public function test_the_owner_deletes_a_profile_that_never_fought(): void
    {
        $removed = null;

        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn($this->player());
        $playerRepository->expects($this->once())->method('remove')->willReturnCallback(
            static function (Player $player) use (&$removed): void {
                $removed = $player;
            }
        );

        $this->handler($playerRepository, self::OWNER_ID, competes: false)($this->command());

        $this->assertInstanceOf(Player::class, $removed);
        $this->assertSame(self::PLAYER_ID, $removed->getId()->getValue());
    }

    public function test_an_unknown_profile_deletes_nothing(): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn(null);
        $playerRepository->expects($this->never())->method('remove');

        $this->expectException(NotFoundException::class);

        $this->handler($playerRepository, self::OWNER_ID, competes: false)($this->command());
    }

    public function test_another_account_cannot_delete_the_profile(): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn($this->player());
        $playerRepository->expects($this->never())->method('remove');

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('belongs to another account');

        $this->handler($playerRepository, self::STRANGER_ID, competes: false)($this->command());
    }

    public function test_a_profile_that_takes_part_in_fights_is_kept(): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn($this->player());
        $playerRepository->expects($this->never())->method('remove');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIsOrContains('takes part in fights');

        $this->handler($playerRepository, self::OWNER_ID, competes: true)($this->command());
    }

    /** @return iterable<string, array{string, string}> */
    public static function placesInAClan(): iterable
    {
        yield 'the leader' => ['leadership', 'this player profile leads a clan and cannot be deleted'];
        yield 'a member' => ['membership', 'this player profile belongs to a clan: leave it first'];
        yield 'an invited player' => ['invitation', 'this player profile is invited to a clan: decline the invitation first'];
        yield 'a player asking to join' => ['joinRequest', 'this player profile asks to join a clan: withdraw the request first'];
    }

    #[DataProvider('placesInAClan')]
    public function test_a_profile_with_a_place_in_a_clan_is_kept_and_told_what_to_do(string $place, string $reason): void
    {
        $clan = 'leadership' === $place
            ? self::aClan(self::CLAN_ID, self::GAME_ID, self::PLAYER_ID)
            : self::aClan(self::CLAN_ID, self::GAME_ID, self::OTHER_LEADER_ID);
        $membership = 'leadership' === $place ? self::leadership($clan) : self::{$place}($clan, self::PLAYER_ID);

        $this->expectRefusal([$membership], $reason);
    }

    public function test_the_membership_is_named_before_an_invitation_to_another_clan(): void
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::OTHER_LEADER_ID);
        $other = self::aClan(self::OTHER_CLAN_ID, self::GAME_ID, self::OTHER_LEADER_ID, 'OTH');

        $this->expectRefusal(
            [self::invitation($other, self::PLAYER_ID), self::membership($clan, self::PLAYER_ID)],
            'this player profile belongs to a clan: leave it first',
        );
    }

    public function test_the_deletion_is_announced_once_the_profile_is_gone(): void
    {
        $dispatched = [];

        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn($this->player());

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (object $event) use (&$dispatched): object {
                $dispatched[] = $event;

                return $event;
            }
        );

        new DeletePlayerHandler(
            $playerRepository,
            $this->currentUserProvider(self::OWNER_ID),
            $this->competitorIdProvider(false),
            $this->createStub(ClanMemberRepositoryInterface::class),
            $eventDispatcher,
            $this->createStub(SerializerInterface::class),
        )($this->command());

        $this->assertCount(1, $dispatched);
        $this->assertInstanceOf(PlayerDeletedEvent::class, $dispatched[0]);
        $this->assertSame(self::PLAYER_ID, $dispatched[0]->getPlayerId()->getValue());
    }

    public function test_the_profile_is_answered_one_last_time(): void
    {
        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn($this->player());

        $serializer = $this->createStub(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{"battletag":"PlayerOne#1234"}');

        $handler = $this->handler($playerRepository, self::OWNER_ID, competes: false, serializer: $serializer);

        $this->assertSame('{"battletag":"PlayerOne#1234"}', $handler($this->command()));
    }

    private function handler(
        PlayerRepositoryInterface $playerRepository,
        string $currentUserId,
        bool $competes,
        ?SerializerInterface $serializer = null,
        ?ClanMemberRepositoryInterface $clanMemberRepository = null,
    ): DeletePlayerHandler {
        return new DeletePlayerHandler(
            $playerRepository,
            $this->currentUserProvider($currentUserId),
            $this->competitorIdProvider($competes),
            $clanMemberRepository ?? $this->createStub(ClanMemberRepositoryInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $serializer ?? $this->createStub(SerializerInterface::class),
        );
    }

    /** @param list<ClanMember> $places */
    private function expectRefusal(array $places, string $reason): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn($this->player());
        $playerRepository->expects($this->never())->method('remove');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage($reason);

        $this->handler(
            $playerRepository,
            self::OWNER_ID,
            competes: false,
            clanMemberRepository: $this->repositoryStub(ClanMemberRepositoryInterface::class, $places),
        )($this->command());
    }

    private function currentUserProvider(string $userId): CurrentUserProviderInterface
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn(new User($userId));

        return $currentUserProvider;
    }

    private function competitorIdProvider(bool $competes): CompetitorIdProviderInterface
    {
        $competitorIdProvider = $this->createStub(CompetitorIdProviderInterface::class);
        $competitorIdProvider->method('takesPartInFights')->willReturn($competes);

        return $competitorIdProvider;
    }

    private function player(): Player
    {
        $player = Player::create(
            new PlayerId(self::PLAYER_ID),
            'PlayerOne#1234',
            new GameId(self::GAME_ID),
            new UserId(self::OWNER_ID),
        );
        $player->pullDomainEvents();

        return $player;
    }

    private function command(): DeletePlayerCommand
    {
        $command = new DeletePlayerCommand();
        $command->setPlayerId(self::PLAYER_ID);

        return $command;
    }
}
