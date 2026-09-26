<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Player\Application;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Player\Application\Model\UpdatePlayerCommand;
use App\Competition\Profile\Player\Application\Service\UpdatePlayerHandler;
use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UpdatePlayerHandlerTest extends TestCase
{
    private const PLAYER_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const OWNER_ID = '33333333-3333-4333-8333-333333333333';
    private const STRANGER_ID = '44444444-4444-4444-8444-444444444444';

    public function test_the_owner_renames_their_own_profile(): void
    {
        $player = $this->player();
        $saved = null;

        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn($player);
        $playerRepository->expects($this->once())->method('save')->willReturnCallback(
            static function (Player $updated) use (&$saved): void {
                $saved = $updated;
            }
        );

        $this->handler($playerRepository, self::OWNER_ID)($this->command());

        $this->assertInstanceOf(Player::class, $saved);
        $this->assertSame('PlayerTwo#5678', $saved->getBattletag());
    }

    public function test_the_profile_is_read_by_its_own_identifier(): void
    {
        $criteria = null;

        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturnCallback(
            function (array $given) use (&$criteria): Player {
                $criteria = $given;

                return $this->player();
            }
        );

        $this->handler($playerRepository, self::OWNER_ID)($this->command());

        $this->assertSame(['id' => self::PLAYER_ID], $criteria);
    }

    public function test_an_unknown_profile_writes_nothing(): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn(null);
        $playerRepository->expects($this->never())->method('save');

        $this->expectException(NotFoundException::class);

        $this->handler($playerRepository, self::OWNER_ID)($this->command());
    }

    public function test_another_account_cannot_rename_the_profile(): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn($this->player());
        $playerRepository->expects($this->never())->method('save');

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('belongs to another account');

        $this->handler($playerRepository, self::STRANGER_ID)($this->command());
    }

    public function test_an_empty_battletag_is_refused_before_any_read(): void
    {
        $playerRepository = $this->createMock(PlayerRepositoryInterface::class);
        $playerRepository->expects($this->never())->method('findOneBy');
        $playerRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);

        $this->handler($playerRepository, self::OWNER_ID)($this->command('  '));
    }

    public function test_the_updated_profile_is_answered_to_the_caller(): void
    {
        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn($this->player());

        $serializer = $this->createStub(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{"battletag":"PlayerTwo#5678"}');

        $handler = $this->handler($playerRepository, self::OWNER_ID, $serializer);

        $this->assertSame('{"battletag":"PlayerTwo#5678"}', $handler($this->command()));
    }

    private function handler(
        PlayerRepositoryInterface $playerRepository,
        string $currentUserId,
        ?SerializerInterface $serializer = null,
    ): UpdatePlayerHandler {
        return new UpdatePlayerHandler(
            $playerRepository,
            $this->currentUserProvider($currentUserId),
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

    private function command(string $battletag = 'PlayerTwo#5678'): UpdatePlayerCommand
    {
        $command = new UpdatePlayerCommand();
        $command->setPlayerId(self::PLAYER_ID);
        $command->setBattletag($battletag);

        return $command;
    }
}
