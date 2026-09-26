<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Player\Application;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Event\OnFightCreationRequestedEvent;
use App\Competition\Profile\Player\Application\Event\OnFightPlayersVerifiedEvent;
use App\Competition\Profile\Player\Application\EventSubscriber\FightCreationRequestedEventSubscriber;
use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class FightCreationRequestedEventSubscriberTest extends TestCase
{
    private const GAME_ID = '00000000-0000-4000-8000-000000000000';
    private const PLAYER_ONE_ID = '11111111-1111-4111-8111-111111111111';
    private const PLAYER_TWO_ID = '22222222-2222-4222-8222-222222222222';
    private const OWNER_ONE_ID = '33333333-3333-4333-8333-333333333333';
    private const OWNER_TWO_ID = '44444444-4444-4444-8444-444444444444';
    private const BYSTANDER_ID = '55555555-5555-4555-8555-555555555555';

    public function test_a_participant_may_open_the_fight(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(OnFightPlayersVerifiedEvent::class));

        $this->subscriber(self::OWNER_ONE_ID, $eventDispatcher)->validatePlayers($this->requestedEvent());
    }

    public function test_the_second_participant_may_open_it_too(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch');

        $this->subscriber(self::OWNER_TWO_ID, $eventDispatcher)->validatePlayers($this->requestedEvent());
    }

    public function test_a_bystander_cannot_open_a_fight_between_two_others(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessageIsOrContains('you do not take part in this fight');

        $this->subscriber(self::BYSTANDER_ID, $eventDispatcher)->validatePlayers($this->requestedEvent());
    }

    public function test_a_player_cannot_fight_itself(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(ValidationException::class);

        $this->subscriber(self::OWNER_ONE_ID, $eventDispatcher)->validatePlayers(
            new OnFightCreationRequestedEvent(self::PLAYER_ONE_ID, self::PLAYER_ONE_ID)
        );
    }

    public function test_an_unknown_player_stops_the_chain(): void
    {
        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturn(null);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $subscriber = new FightCreationRequestedEventSubscriber(
            $playerRepository,
            $this->currentUserProvider(self::OWNER_ONE_ID),
            $eventDispatcher,
        );

        $this->expectException(NotFoundException::class);

        $subscriber->validatePlayers($this->requestedEvent());
    }

    private function subscriber(
        string $callerId,
        EventDispatcherInterface $eventDispatcher,
    ): FightCreationRequestedEventSubscriber {
        $playerRepository = $this->createStub(PlayerRepositoryInterface::class);
        $playerRepository->method('findOneBy')->willReturnCallback(
            fn (array $criteria): Player => self::PLAYER_ONE_ID === $criteria['id']
                ? $this->player(self::PLAYER_ONE_ID, self::OWNER_ONE_ID)
                : $this->player(self::PLAYER_TWO_ID, self::OWNER_TWO_ID)
        );

        return new FightCreationRequestedEventSubscriber(
            $playerRepository,
            $this->currentUserProvider($callerId),
            $eventDispatcher,
        );
    }

    private function player(string $playerId, string $ownerId): Player
    {
        return Player::create(
            new PlayerId($playerId),
            'PlayerOne#1234',
            new GameId(self::GAME_ID),
            new UserId($ownerId),
        );
    }

    private function currentUserProvider(string $callerId): CurrentUserProviderInterface
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn(new User($callerId));

        return $currentUserProvider;
    }

    private function requestedEvent(): OnFightCreationRequestedEvent
    {
        return new OnFightCreationRequestedEvent(self::PLAYER_ONE_ID, self::PLAYER_TWO_ID);
    }
}
