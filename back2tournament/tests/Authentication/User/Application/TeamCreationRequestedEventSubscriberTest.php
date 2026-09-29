<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Application;

use App\Authentication\User\Application\Event\OnTeamCreationUserVerifiedEvent;
use App\Authentication\User\Application\EventSubscriber\TeamCreationRequestedEventSubscriber;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Team\Application\Event\OnTeamCreationRequestedEvent;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TeamCreationRequestedEventSubscriberTest extends TestCase
{
    private const USER_ID = '22222222-2222-2222-2222-222222222222';
    private const CLAN_ID = '44444444-4444-4444-8444-444444444444';
    private const PLAYER_ID = '11111111-1111-4111-8111-111111111111';
    private const LEADER_ID = '33333333-3333-4333-8333-333333333333';
    private const CREATED_TEAM = '{"name":"Team"}';

    public function test_dispatch_when_user_has_role_user(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(OnTeamCreationUserVerifiedEvent::class))
            ->willReturnCallback(self::createsTheTeam(...));

        $subscriber = new TeamCreationRequestedEventSubscriber(
            $this->currentUserProvider(new User(self::USER_ID)),
            $eventDispatcher,
        );

        $subscriber->validateUser($this->requestedEvent());
    }

    public function test_the_authenticated_caller_is_the_one_carried_downstream(): void
    {
        $dispatched = null;

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (object $event) use (&$dispatched): object {
                $dispatched = $event;

                return self::createsTheTeam($event);
            }
        );

        $subscriber = new TeamCreationRequestedEventSubscriber(
            $this->currentUserProvider(new User(self::USER_ID)),
            $eventDispatcher,
        );

        $subscriber->validateUser($this->requestedEvent());

        $this->assertInstanceOf(OnTeamCreationUserVerifiedEvent::class, $dispatched);
        $this->assertSame(self::USER_ID, $dispatched->getUser());
        $this->assertSame(self::CLAN_ID, $dispatched->getClan());
        $this->assertSame(2, $dispatched->getSize());
        $this->assertSame([self::LEADER_ID, self::PLAYER_ID], $dispatched->getPlayers());
        $this->assertSame(self::LEADER_ID, $dispatched->getLeader());
    }

    public function test_the_created_team_travels_back_on_the_requested_event(): void
    {
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(self::createsTheTeam(...));

        $subscriber = new TeamCreationRequestedEventSubscriber(
            $this->currentUserProvider(new User(self::USER_ID)),
            $eventDispatcher,
        );

        $event = $this->requestedEvent();
        $subscriber->validateUser($event);

        $this->assertSame(self::CREATED_TEAM, $event->getCreatedTeam());
    }

    public function test_an_unauthenticated_caller_stops_the_chain(): void
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider
            ->method('getUser')
            ->willThrowException(new PermissionDeniedException('no authenticated user'));

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $subscriber = new TeamCreationRequestedEventSubscriber($currentUserProvider, $eventDispatcher);

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('no authenticated user');

        $subscriber->validateUser($this->requestedEvent());
    }

    private static function createsTheTeam(object $event): object
    {
        if ($event instanceof OnTeamCreationUserVerifiedEvent) {
            $event->setCreatedTeam(self::CREATED_TEAM);
        }

        return $event;
    }

    private function requestedEvent(): OnTeamCreationRequestedEvent
    {
        return new OnTeamCreationRequestedEvent('Team', self::CLAN_ID, 2, [self::LEADER_ID, self::PLAYER_ID], self::LEADER_ID);
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
