<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Application;

use App\Authentication\User\Application\Model\ChangeAvatarCommand;
use App\Authentication\User\Application\Service\ChangeAvatarHandler;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Event\UserAvatarChangedEvent;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Shared\Domain\Provider\PlayerProfileProviderInterface;
use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Shared\Domain\Provider\ImageProviderInterface;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\UploadedImageValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ChangeAvatarHandlerTest extends TestCase
{
    private const USER_ID = '11111111-1111-4111-8111-111111111111';
    private const PLAYER_ID = '22222222-2222-4222-8222-222222222222';
    private const GAME_ID = '33333333-3333-4333-8333-333333333333';

    /** A 32 x 24 PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAYCAIAAAAUMWhjAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAJklEQVRIiWM8oaHBQEvARFPTRy0YtWDUglELRi0YtWDUglELqAYA1J4BSIDvLE0AAAAASUVORK5CYII=';

    /** @var list<string> what happened, in order */
    private array $log = [];

    public function test_a_user_gives_themselves_a_picture_and_the_former_one_goes_once_it_is_saved(): void
    {
        $user = $this->user(avatar: 'avatars/old.webp');

        $payload = $this->read($this->handler($user)(new ChangeAvatarCommand(base64_decode(self::PNG, true))));

        $this->assertSame('avatars/new.webp', $user->getAvatar());
        $this->assertSame(['store avatars image/png', 'save', 'dispatch '.UserAvatarChangedEvent::class, 'remove avatars/old.webp'], $this->log);
        // As GET /api/users/me answers.
        $this->assertSame([self::USER_ID, [['id' => self::PLAYER_ID, 'battletag' => 'Demo#1000', 'game' => self::GAME_ID]]], [$payload['id'], $payload['players']]);
    }

    public function test_a_user_takes_their_picture_away(): void
    {
        $user = $this->user(avatar: 'avatars/old.webp');

        $this->handler($user)(new ChangeAvatarCommand(null));

        $this->assertNull($user->getAvatar());
        $this->assertSame(['save', 'dispatch '.UserAvatarChangedEvent::class, 'remove avatars/old.webp'], $this->log);
    }

    public function test_taking_away_a_picture_the_user_does_not_have_changes_nothing(): void
    {
        $this->handler($this->user(avatar: null))(new ChangeAvatarCommand(null));

        $this->assertSame([], $this->log);
    }

    public function test_a_file_that_is_no_image_is_refused_before_anything_is_stored(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler($this->user(avatar: null), untouched: true)(new ChangeAvatarCommand('not an image, only words'));
    }

    /**
     * @param bool $untouched whether nothing may be stored, saved, removed or announced
     */
    private function handler(User $user, bool $untouched = false): ChangeAvatarHandler
    {
        [$userRepository, $imageProvider, $eventDispatcher] = $untouched ? $this->untouched() : $this->logged();

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn($user);

        $playerProfileProvider = $this->createStub(PlayerProfileProviderInterface::class);
        $playerProfileProvider->method('byUser')->willReturn([['id' => self::PLAYER_ID, 'battletag' => 'Demo#1000', 'game' => self::GAME_ID]]);

        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturn(['id' => self::USER_ID]);

        return new ChangeAvatarHandler($currentUserProvider, $userRepository, $imageProvider, $playerProfileProvider, $eventDispatcher, $normalizer);
    }

    /**
     * @return array{UserRepositoryInterface&Stub, ImageProviderInterface, EventDispatcherInterface}
     */
    private function logged(): array
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('save')->willReturnCallback(function (): void {
            $this->log[] = 'save';
        });

        $imageProvider = $this->createStub(ImageProviderInterface::class);
        $imageProvider->method('store')->willReturnCallback(function (UploadedImageValueObject $image, ImageKind $kind): string {
            $this->log[] = \sprintf('store %s %s', $kind->value, $image->getType());

            return 'avatars/new.webp';
        });
        $imageProvider->method('remove')->willReturnCallback(function (?string $key): void {
            $this->log[] = 'remove '.$key;
        });

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(function (object $event): object {
            $this->log[] = 'dispatch '.$event::class;

            return $event;
        });

        return [$userRepository, $imageProvider, $eventDispatcher];
    }

    /**
     * @return array{UserRepositoryInterface&MockObject, ImageProviderInterface, EventDispatcherInterface}
     */
    private function untouched(): array
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method('save');

        $imageProvider = $this->createMock(ImageProviderInterface::class);
        $imageProvider->expects($this->never())->method('store');
        $imageProvider->expects($this->never())->method('remove');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        return [$userRepository, $imageProvider, $eventDispatcher];
    }

    private function user(?string $avatar): User
    {
        $user = new User(self::USER_ID);
        if (null !== $avatar) {
            User::changeAvatar($user, $avatar);
        }
        $user->pullDomainEvents();

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
