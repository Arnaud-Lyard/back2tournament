<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Application;

use App\Authentication\User\Application\Event\OnUpdateFightResultsVerifiedEvent;
use App\Authentication\User\Application\EventSubscriber\UpdateFightResultsEventSubscriber;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Event\OnUpdateFightResultsEvent;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UpdateFightResultsEventSubscriberTest extends TestCase
{
    private const USER_ID = '22222222-2222-2222-2222-222222222222';
    private const FIGHT_ID = '55555555-5555-4555-8555-555555555555';
    private const UPDATED_FIGHT = '{"status":"reporting"}';

    /**
     * The side that declares is resolved from the JWT identity downstream, so
     * the verified event carries that user along with the scores as sent.
     */
    public function test_the_authenticated_caller_is_the_one_carried_downstream(): void
    {
        $dispatched = null;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch')->willReturnCallback(
            static function (object $event) use (&$dispatched): object {
                $dispatched = $event;

                return self::updatesTheFight($event);
            }
        );

        $subscriber = new UpdateFightResultsEventSubscriber($this->currentUserProvider(), $eventDispatcher);
        $subscriber->onUpdateFightResults(new OnUpdateFightResultsEvent(self::FIGHT_ID, 3, 1));

        $this->assertInstanceOf(OnUpdateFightResultsVerifiedEvent::class, $dispatched);
        $this->assertSame(self::FIGHT_ID, $dispatched->getFight());
        $this->assertSame(self::USER_ID, $dispatched->getUser());
        $this->assertSame(3, $dispatched->getScore());
        $this->assertSame(1, $dispatched->getOpponentScore());
    }

    public function test_the_updated_fight_travels_back_on_the_requested_event(): void
    {
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(self::updatesTheFight(...));

        $event = new OnUpdateFightResultsEvent(self::FIGHT_ID, 3, 1);
        new UpdateFightResultsEventSubscriber($this->currentUserProvider(), $eventDispatcher)->onUpdateFightResults($event);

        $this->assertSame(self::UPDATED_FIGHT, $event->getUpdatedFight());
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

        new UpdateFightResultsEventSubscriber($currentUserProvider, $eventDispatcher)
            ->onUpdateFightResults(new OnUpdateFightResultsEvent(self::FIGHT_ID, 3, 1));
    }

    private static function updatesTheFight(object $event): object
    {
        if ($event instanceof OnUpdateFightResultsVerifiedEvent) {
            $event->setUpdatedFight(self::UPDATED_FIGHT);
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
