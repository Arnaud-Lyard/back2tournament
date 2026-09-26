<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Game\Application;

use App\Authentication\User\Application\Event\OnGameCreationAdminVerifiedEvent;
use App\Competition\Profile\Game\Application\EventSubscriber\GameCreationAdminVerifiedEventSubscriber;
use App\Competition\Profile\Game\Application\Model\CreateGameCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class GameCreationAdminVerifiedEventSubscriberTest extends TestCase
{
    private const ADMIN_ID = '22222222-2222-2222-2222-222222222222';
    private const CREATED_GAME = '{"title":"Rocket League"}';

    public function test_the_verified_request_creates_the_game_and_hands_its_json_back(): void
    {
        $dispatched = null;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())->method('dispatch')->willReturnCallback(
            static function (object $command) use (&$dispatched): Envelope {
                $dispatched = $command;

                return new Envelope($command, [new HandledStamp(self::CREATED_GAME, 'CreateGameHandler::__invoke')]);
            }
        );

        $event = new OnGameCreationAdminVerifiedEvent('Rocket League', self::ADMIN_ID, [1, 2, 3]);
        new GameCreationAdminVerifiedEventSubscriber($messageBus)->createGame($event);

        $this->assertInstanceOf(CreateGameCommand::class, $dispatched);
        $this->assertSame('Rocket League', $dispatched->getTitle());
        $this->assertSame([1, 2, 3], $dispatched->getTeamSizes());
        $this->assertSame(self::CREATED_GAME, $event->getCreatedGame());
    }
}
