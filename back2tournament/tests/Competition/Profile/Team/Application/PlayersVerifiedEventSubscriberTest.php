<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Team\Application;

use App\Competition\Profile\Player\Application\Event\OnPlayersVerifiedEvent;
use App\Competition\Profile\Team\Application\Model\CreateTeamCommand;
use App\Competition\Profile\Team\Application\EventSubscriber\PlayersVerifiedEventSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class PlayersVerifiedEventSubscriberTest extends TestCase
{
    private const USER_ID = '00000000-0000-4000-8000-000000000000';
    private const PLAYER_ID = '33333333-3333-4333-8333-333333333333';
    private const LEADER_ID = '44444444-4444-4444-8444-444444444444';

    public function test_the_verified_player_reaches_the_command(): void
    {
        $command = $this->dispatch($this->verifiedEvent());

        $this->assertInstanceOf(CreateTeamCommand::class, $command);
        $this->assertSame(self::PLAYER_ID, $command->getPlayer());
    }

    public function test_the_name_and_the_leader_are_carried_over(): void
    {
        $command = $this->dispatch($this->verifiedEvent());

        $this->assertInstanceOf(CreateTeamCommand::class, $command);
        $this->assertSame('Falcons', $command->getName());
        $this->assertSame(self::LEADER_ID, $command->getLeader());
    }

    private function dispatch(OnPlayersVerifiedEvent $event): ?object
    {
        $dispatched = null;

        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(
            static function (object $message) use (&$dispatched): Envelope {
                $dispatched = $message;

                return new Envelope($message);
            }
        );

        new PlayersVerifiedEventSubscriber($messageBus)->createTeam($event);

        return $dispatched;
    }

    private function verifiedEvent(): OnPlayersVerifiedEvent
    {
        return new OnPlayersVerifiedEvent('Falcons', self::USER_ID, self::PLAYER_ID, self::LEADER_ID);
    }
}
