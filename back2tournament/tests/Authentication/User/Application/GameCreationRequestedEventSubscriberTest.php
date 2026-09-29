<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Application;

use App\Authentication\User\Application\Event\OnGameCreationAdminVerifiedEvent;
use App\Authentication\User\Application\EventSubscriber\GameCreationRequestedEventSubscriber;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Game\Application\Event\OnGameCreationRequestedEvent;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GameCreationRequestedEventSubscriberTest extends TestCase
{
    private const ADMIN_ID = '22222222-2222-2222-2222-222222222222';
    private const PLAIN_ID = '11111111-1111-1111-1111-111111111111';
    private const CREATED_GAME = '{"title":"Game"}';

    public function test_dispatch_on_right_privilege(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(OnGameCreationAdminVerifiedEvent::class))
            ->willReturnCallback(self::createsTheGame(...));

        $subscriber = new GameCreationRequestedEventSubscriber(
            $this->currentUserProvider($this->admin()),
            $eventDispatcher,
        );

        $subscriber->validateUser(new OnGameCreationRequestedEvent('Game', [1]));
    }

    public function test_throw_on_invalid_privilege(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $subscriber = new GameCreationRequestedEventSubscriber(
            $this->currentUserProvider(new User(self::PLAIN_ID)),
            $eventDispatcher,
        );

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('the user does not have the necessary permissions');

        $subscriber->validateUser(new OnGameCreationRequestedEvent('Game', [1]));
    }

    public function test_the_authenticated_caller_is_the_one_carried_downstream(): void
    {
        $dispatched = null;

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (object $event) use (&$dispatched): object {
                $dispatched = $event;

                return self::createsTheGame($event);
            }
        );

        $subscriber = new GameCreationRequestedEventSubscriber(
            $this->currentUserProvider($this->admin()),
            $eventDispatcher,
        );

        $subscriber->validateUser(new OnGameCreationRequestedEvent('Game', [1, 2, 3]));

        $this->assertInstanceOf(OnGameCreationAdminVerifiedEvent::class, $dispatched);
        $this->assertSame(self::ADMIN_ID, $dispatched->getUser());
        $this->assertSame('Game', $dispatched->getTitle());
        $this->assertSame([1, 2, 3], $dispatched->getTeamSizes());
    }

    public function test_the_created_game_travels_back_on_the_requested_event(): void
    {
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(self::createsTheGame(...));

        $subscriber = new GameCreationRequestedEventSubscriber(
            $this->currentUserProvider($this->admin()),
            $eventDispatcher,
        );

        $event = new OnGameCreationRequestedEvent('Game', [1]);
        $subscriber->validateUser($event);

        $this->assertSame(self::CREATED_GAME, $event->getCreatedGame());
    }

    public function test_an_unauthenticated_caller_stops_the_chain(): void
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider
            ->method('getUser')
            ->willThrowException(new PermissionDeniedException('no authenticated user'));

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $subscriber = new GameCreationRequestedEventSubscriber($currentUserProvider, $eventDispatcher);

        $this->expectException(PermissionDeniedException::class);

        $subscriber->validateUser(new OnGameCreationRequestedEvent('Game', [1]));
    }

    private static function createsTheGame(object $event): object
    {
        if ($event instanceof OnGameCreationAdminVerifiedEvent) {
            $event->setCreatedGame(self::CREATED_GAME);
        }

        return $event;
    }

    private function admin(): User
    {
        return new User(self::ADMIN_ID)->setRoles(['ROLE_ADMIN']);
    }

    private function currentUserProvider(User $user): CurrentUserProviderInterface
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn($user);
        $currentUserProvider
            ->method('isGranted')
            ->willReturnCallback(static fn (string $role): bool => \in_array($role, $user->getRoles(), true));

        return $currentUserProvider;
    }
}
