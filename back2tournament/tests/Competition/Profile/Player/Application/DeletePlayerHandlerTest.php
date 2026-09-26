<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Player\Application;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeletePlayerHandlerTest extends TestCase
{
    private const PLAYER_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const OWNER_ID = '33333333-3333-4333-8333-333333333333';
    private const STRANGER_ID = '44444444-4444-4444-8444-444444444444';

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
    ): DeletePlayerHandler {
        return new DeletePlayerHandler(
            $playerRepository,
            $this->currentUserProvider($currentUserId),
            $this->competitorIdProvider($competes),
            $this->createStub(EventDispatcherInterface::class),
            $serializer ?? $this->createStub(SerializerInterface::class),
        );
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

    /**
     * A profile as the handler meets it: read back from the repository, so the
     * creation it was born with is long dispatched.
     */
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
