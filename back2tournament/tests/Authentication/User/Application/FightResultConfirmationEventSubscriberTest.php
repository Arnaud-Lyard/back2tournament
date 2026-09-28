<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Application;

use App\Authentication\User\Application\Event\OnFightResultsConfirmationUserVerified;
use App\Authentication\User\Application\EventSubscriber\FightResultConfirmationEventSubscriber;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Event\OnFightResultConfirmationEvent;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class FightResultConfirmationEventSubscriberTest extends TestCase
{
    private const USER_ID = '22222222-2222-2222-2222-222222222222';
    private const FIGHT_ID = '55555555-5555-4555-8555-555555555555';
    private const CONFIRMED_FIGHT = '{"status":"finished"}';

    public function test_the_authenticated_caller_is_the_one_carried_downstream(): void
    {
        $dispatched = null;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch')->willReturnCallback(
            static function (object $event) use (&$dispatched): object {
                $dispatched = $event;

                return self::confirmsTheFight($event);
            }
        );

        new FightResultConfirmationEventSubscriber($this->currentUserProvider(), $eventDispatcher)
            ->onFightResultConfirmation(new OnFightResultConfirmationEvent(self::FIGHT_ID));

        $this->assertInstanceOf(OnFightResultsConfirmationUserVerified::class, $dispatched);
        $this->assertSame(self::FIGHT_ID, $dispatched->getFight());
        $this->assertSame(self::USER_ID, $dispatched->getUser());
    }

    public function test_the_confirmed_fight_travels_back_on_the_requested_event(): void
    {
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(self::confirmsTheFight(...));

        $event = new OnFightResultConfirmationEvent(self::FIGHT_ID);
        new FightResultConfirmationEventSubscriber($this->currentUserProvider(), $eventDispatcher)->onFightResultConfirmation($event);

        $this->assertSame(self::CONFIRMED_FIGHT, $event->getConfirmedFight());
    }

    public function test_an_unauthenticated_caller_stops_the_chain(): void
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider
            ->method('getUser')
            ->willThrowException(new PermissionDeniedException('no authenticated user'));

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(PermissionDeniedException::class);

        new FightResultConfirmationEventSubscriber($currentUserProvider, $eventDispatcher)
            ->onFightResultConfirmation(new OnFightResultConfirmationEvent(self::FIGHT_ID));
    }

    private static function confirmsTheFight(object $event): object
    {
        if ($event instanceof OnFightResultsConfirmationUserVerified) {
            $event->setConfirmedFight(self::CONFIRMED_FIGHT);
        }

        return $event;
    }

    private function currentUserProvider(): CurrentUserProviderInterface
    {
        $user = new User(self::USER_ID);

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn($user);
        $currentUserProvider
            ->method('isGranted')
            ->willReturnCallback(static fn (string $role): bool => \in_array($role, $user->getRoles(), true));

        return $currentUserProvider;
    }
}
