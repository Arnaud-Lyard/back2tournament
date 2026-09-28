<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Team\Application;

use App\Authentication\User\Application\Event\OnTeamCreationUserVerifiedEvent;
use App\Competition\Profile\Team\Application\EventSubscriber\TeamCreationUserVerifiedEventSubscriber;
use App\Competition\Profile\Team\Application\Model\CreateTeamCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class TeamCreationUserVerifiedEventSubscriberTest extends TestCase
{
    private const USER_ID = '22222222-2222-2222-2222-222222222222';
    private const CLAN_ID = '44444444-4444-4444-8444-444444444444';
    private const LEADER_ID = '33333333-3333-4333-8333-333333333333';
    private const MATE_ID = '11111111-1111-4111-8111-111111111111';
    private const CREATED_TEAM = '{"name":"Falcons Duo"}';

    public function test_the_verified_request_creates_the_team_for_the_verified_user(): void
    {
        $dispatched = null;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())->method('dispatch')->willReturnCallback(
            static function (object $command) use (&$dispatched): Envelope {
                $dispatched = $command;

                return new Envelope($command, [new HandledStamp(self::CREATED_TEAM, 'CreateTeamHandler::__invoke')]);
            }
        );

        $event = new OnTeamCreationUserVerifiedEvent('Falcons Duo', self::USER_ID, self::CLAN_ID, 2, [self::LEADER_ID, self::MATE_ID], self::LEADER_ID);
        new TeamCreationUserVerifiedEventSubscriber($messageBus)->createTeam($event);

        $this->assertInstanceOf(CreateTeamCommand::class, $dispatched);
        $this->assertSame(self::USER_ID, $dispatched->getUser());
        $this->assertSame(self::CLAN_ID, $dispatched->getClan());
        $this->assertSame('Falcons Duo', $dispatched->getName());
        $this->assertSame(2, $dispatched->getSize());
        $this->assertSame([self::LEADER_ID, self::MATE_ID], $dispatched->getPlayers());
        $this->assertSame(self::LEADER_ID, $dispatched->getLeader());
        $this->assertSame(self::CREATED_TEAM, $event->getCreatedTeam());
    }
}
