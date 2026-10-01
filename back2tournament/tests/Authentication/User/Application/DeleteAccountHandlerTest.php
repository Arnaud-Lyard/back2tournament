<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Application;

use App\Authentication\User\Application\Model\DeleteAccountCommand;
use App\Authentication\User\Application\Service\DeleteAccountHandler;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Event\UserDeletedEvent;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Authentication\User\Domain\Security\PasswordHasherInterface;
use App\Competition\Shared\Domain\Provider\AccountErasureProviderInterface;
use App\Media\Shared\Domain\Provider\ImageProviderInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeleteAccountHandlerTest extends TestCase
{
    private const USER_ID = '11111111-1111-4111-8111-111111111111';

    public function test_the_account_is_erased_its_picture_removed_and_the_others_told(): void
    {
        $user = $this->user();

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->once())->method('save')->with($user);

        $imageProvider = $this->createMock(ImageProviderInterface::class);
        $imageProvider->expects($this->once())->method('remove')->with('avatars/demo.webp');

        $dispatched = [];
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (object $event) use (&$dispatched): object {
                $dispatched[] = $event;

                return $event;
            }
        );

        $answer = $this->handler($user, userRepository: $userRepository, imageProvider: $imageProvider, eventDispatcher: $eventDispatcher)(
            new DeleteAccountCommand('Seed1234!'),
        );

        $this->assertSame(['deleted' => true], json_decode($answer, true, 512, JSON_THROW_ON_ERROR));
        $this->assertTrue($user->isDeleted());
        $this->assertSame('hashed-unusable', $user->getPassword());
        $this->assertCount(1, $dispatched);
        $this->assertInstanceOf(UserDeletedEvent::class, $dispatched[0]);
    }

    public function test_a_wrong_password_erases_nothing(): void
    {
        $user = $this->user();

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn($user);
        $currentUserProvider->method('confirmPassword')->willThrowException(new PermissionDeniedException('the password does not match'));

        $accountErasureProvider = $this->createMock(AccountErasureProviderInterface::class);
        $accountErasureProvider->expects($this->never())->method('ensureErasable');

        $this->expectException(PermissionDeniedException::class);

        $this->handler($user, $currentUserProvider, $accountErasureProvider, $this->untouchedUsers(), $this->untouchedImages())(
            new DeleteAccountCommand('nope'),
        );
    }

    public function test_an_account_the_competition_still_needs_is_kept(): void
    {
        $user = $this->user();

        $accountErasureProvider = $this->createStub(AccountErasureProviderInterface::class);
        $accountErasureProvider->method('ensureErasable')->willThrowException(new ConflictException('you lead a clan: dissolve it first'));

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('you lead a clan: dissolve it first');

        $this->handler($user, accountErasureProvider: $accountErasureProvider, userRepository: $this->untouchedUsers(), imageProvider: $this->untouchedImages())(
            new DeleteAccountCommand('Seed1234!'),
        );
    }

    private function user(): User
    {
        $user = new User(self::USER_ID)->setUsername('demo')->setEmail('demo@seed.local')->setPassword('hash');
        User::changeAvatar($user, 'avatars/demo.webp');
        $user->pullDomainEvents();

        return $user;
    }

    private function untouchedUsers(): UserRepositoryInterface
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method('save');

        return $userRepository;
    }

    private function untouchedImages(): ImageProviderInterface
    {
        $imageProvider = $this->createMock(ImageProviderInterface::class);
        $imageProvider->expects($this->never())->method('remove');

        return $imageProvider;
    }

    private function handler(
        User $user,
        ?CurrentUserProviderInterface $currentUserProvider = null,
        ?AccountErasureProviderInterface $accountErasureProvider = null,
        ?UserRepositoryInterface $userRepository = null,
        ?ImageProviderInterface $imageProvider = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): DeleteAccountHandler {
        if (null === $currentUserProvider) {
            $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
            $currentUserProvider->method('getUser')->willReturn($user);
        }

        $passwordHasher = $this->createStub(PasswordHasherInterface::class);
        $passwordHasher->method('hash')->willReturn('hashed-unusable');

        return new DeleteAccountHandler(
            $currentUserProvider,
            $userRepository ?? $this->createStub(UserRepositoryInterface::class),
            $passwordHasher,
            $accountErasureProvider ?? $this->createStub(AccountErasureProviderInterface::class),
            $imageProvider ?? $this->createStub(ImageProviderInterface::class),
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
        );
    }
}
