<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Game\Application;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Game\Application\Model\CreateGameCommand;
use App\Competition\Profile\Game\Application\Model\UpdateGameCommand;
use App\Competition\Profile\Game\Application\Service\CreateGameHandler;
use App\Competition\Profile\Game\Application\Service\UpdateGameHandler;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamSizeValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GameHandlersTest extends TestCase
{
    private const GAME_ID = '11111111-1111-4111-8111-111111111111';

    public function test_an_admin_creates_a_game_with_its_formats(): void
    {
        $saved = null;

        $gameRepository = $this->createMock(GameRepositoryInterface::class);
        $gameRepository->expects($this->once())->method('save')->willReturnCallback(
            static function (Game $game) use (&$saved): void {
                $saved = $game;
            }
        );

        $this->createHandler($gameRepository)(new CreateGameCommand('Rocket League', [1, 2, 3]));

        $this->assertInstanceOf(Game::class, $saved);
        $this->assertSame('Rocket League', $saved->getTitle());
        $this->assertSame([1, 2, 3], $saved->getTeamSizes());
    }

    public function test_an_invalid_format_is_refused_before_anything_else(): void
    {
        $gameRepository = $this->createMock(GameRepositoryInterface::class);
        $gameRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);

        $this->createHandler($gameRepository)(new CreateGameCommand('Rocket League', [0]));
    }

    public function test_an_admin_opens_new_formats_on_an_existing_game(): void
    {
        $game = Game::create(new GameId(self::GAME_ID), 'Valorant', [new TeamSizeValueObject(1)]);

        $gameRepository = $this->createMock(GameRepositoryInterface::class);
        $gameRepository->method('findOneBy')->willReturn($game);
        $gameRepository->expects($this->once())->method('save');

        $this->updateHandler($gameRepository, admin: true)(new UpdateGameCommand(self::GAME_ID, null, [1, 5]));

        $this->assertSame([1, 5], $game->getTeamSizes());
    }

    public function test_an_unknown_game_is_not_updated(): void
    {
        $gameRepository = $this->createMock(GameRepositoryInterface::class);
        $gameRepository->method('findOneBy')->willReturn(null);
        $gameRepository->expects($this->never())->method('save');

        $this->expectException(NotFoundException::class);

        $this->updateHandler($gameRepository, admin: true)(new UpdateGameCommand(self::GAME_ID, 'Valorant', null));
    }

    public function test_only_an_admin_updates_a_game(): void
    {
        $gameRepository = $this->createMock(GameRepositoryInterface::class);
        $gameRepository->expects($this->never())->method('save');

        $this->expectException(PermissionDeniedException::class);

        $this->updateHandler($gameRepository, admin: false)(new UpdateGameCommand(self::GAME_ID, 'Valorant', null));
    }

    private function createHandler(GameRepositoryInterface $gameRepository): CreateGameHandler
    {
        return new CreateGameHandler(
            $gameRepository,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
        );
    }

    private function updateHandler(GameRepositoryInterface $gameRepository, bool $admin): UpdateGameHandler
    {
        return new UpdateGameHandler(
            $gameRepository,
            $this->currentUserProvider($admin),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SerializerInterface::class),
        );
    }

    private function currentUserProvider(bool $admin): CurrentUserProviderInterface
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('isGranted')->willReturn($admin);

        return $currentUserProvider;
    }
}
