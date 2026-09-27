<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Game\Application;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Game\Application\Model\ChangeGameImageCommand;
use App\Competition\Profile\Game\Application\Service\ChangeGameImageHandler;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Event\GameUpdatedEvent;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Shared\Domain\Provider\ImageProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamSizeValueObject;
use App\Shared\ValueObject\UploadedImageValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ChangeGameImageHandlerTest extends TestCase
{
    private const GAME_ID = '11111111-1111-4111-8111-111111111111';

    /** A 32 x 24 PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAYCAIAAAAUMWhjAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAJklEQVRIiWM8oaHBQEvARFPTRy0YtWDUglELRi0YtWDUglELqAYA1J4BSIDvLE0AAAAASUVORK5CYII=';

    /** @var list<string> what happened, in order */
    private array $log = [];

    public function test_an_admin_gives_a_game_its_picture_and_the_former_one_goes_once_it_is_saved(): void
    {
        $game = $this->game(image: 'games/old.webp');

        $json = $this->handler($game)(new ChangeGameImageCommand(self::GAME_ID, base64_decode(self::PNG, true)));

        $this->assertSame('games/new.webp', $game->getImage());
        $this->assertSame(['store games image/png', 'save', 'dispatch '.GameUpdatedEvent::class, 'remove games/old.webp'], $this->log);
        $this->assertSame('{"id":"'.self::GAME_ID.'"}', $json);
    }

    public function test_an_admin_takes_the_picture_away(): void
    {
        $game = $this->game(image: 'games/old.webp');

        $this->handler($game)(new ChangeGameImageCommand(self::GAME_ID, null));

        $this->assertNull($game->getImage());
        $this->assertSame(['save', 'dispatch '.GameUpdatedEvent::class, 'remove games/old.webp'], $this->log);
    }

    public function test_taking_away_a_picture_the_game_does_not_have_changes_nothing(): void
    {
        $this->handler($this->game(image: null))(new ChangeGameImageCommand(self::GAME_ID, null));

        $this->assertSame([], $this->log);
    }

    public function test_only_an_admin_illustrates_a_game(): void
    {
        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('only an administrator illustrates a game');

        $this->handler($this->game(image: null), admin: false, untouched: true)(new ChangeGameImageCommand(self::GAME_ID, base64_decode(self::PNG, true)));
    }

    public function test_a_file_that_is_no_image_is_refused_before_anything_is_read(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler($this->game(image: null), untouched: true)(new ChangeGameImageCommand(self::GAME_ID, 'not an image, only words'));
    }

    public function test_an_unknown_game_is_not_found_and_nothing_is_stored(): void
    {
        $this->expectException(NotFoundException::class);

        $this->handler(null, untouched: true)(new ChangeGameImageCommand(self::GAME_ID, base64_decode(self::PNG, true)));
    }

    /**
     * @param bool $untouched whether nothing may be stored, saved, removed or announced
     */
    private function handler(?Game $game, bool $admin = true, bool $untouched = false): ChangeGameImageHandler
    {
        [$gameRepository, $imageProvider, $eventDispatcher] = $untouched ? $this->untouched() : $this->logged();
        $gameRepository->method('findOneBy')->willReturn($game);

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('isGranted')->willReturnCallback(static fn (string $role): bool => $admin && 'ROLE_ADMIN' === $role);

        $serializer = $this->createStub(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{"id":"'.self::GAME_ID.'"}');

        return new ChangeGameImageHandler($gameRepository, $currentUserProvider, $imageProvider, $eventDispatcher, $serializer);
    }

    /**
     * @return array{GameRepositoryInterface&Stub, ImageProviderInterface, EventDispatcherInterface}
     */
    private function logged(): array
    {
        $gameRepository = $this->createStub(GameRepositoryInterface::class);
        $gameRepository->method('save')->willReturnCallback(function (): void {
            $this->log[] = 'save';
        });

        $imageProvider = $this->createStub(ImageProviderInterface::class);
        $imageProvider->method('store')->willReturnCallback(function (UploadedImageValueObject $image, ImageKind $kind): string {
            $this->log[] = \sprintf('store %s %s', $kind->value, $image->getType());

            return 'games/new.webp';
        });
        $imageProvider->method('remove')->willReturnCallback(function (?string $key): void {
            $this->log[] = 'remove '.$key;
        });

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(function (object $event): object {
            $this->log[] = 'dispatch '.$event::class;

            return $event;
        });

        return [$gameRepository, $imageProvider, $eventDispatcher];
    }

    /**
     * @return array{GameRepositoryInterface&MockObject, ImageProviderInterface, EventDispatcherInterface}
     */
    private function untouched(): array
    {
        $gameRepository = $this->createMock(GameRepositoryInterface::class);
        $gameRepository->expects($this->never())->method('save');

        $imageProvider = $this->createMock(ImageProviderInterface::class);
        $imageProvider->expects($this->never())->method('store');
        $imageProvider->expects($this->never())->method('remove');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        return [$gameRepository, $imageProvider, $eventDispatcher];
    }

    private function game(?string $image): Game
    {
        $game = Game::create(new GameId(self::GAME_ID), 'Rocket League', [new TeamSizeValueObject(1)]);
        if (null !== $image) {
            Game::illustrate($game, $image);
        }
        $game->pullDomainEvents();

        return $game;
    }
}
