<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Application;

use App\Authentication\User\Application\Event\OnFightResultsConfirmationUserVerified;
use App\Authentication\User\Application\Event\OnUpdateFightResultsVerifiedEvent;
use App\Competition\Fight\Application\EventSubscriber\FightResultsConfirmationUserVerifiedEventSubscriber;
use App\Competition\Fight\Application\EventSubscriber\UpdateFightResultsVerifiedEventSubscriber;
use App\Competition\Fight\Application\Model\ConfirmFightResultsCommand;
use App\Competition\Fight\Application\Model\UpdateFightResultsCommand;
use App\Shared\Exception\PermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class FightResultsEventSubscribersTest extends TestCase
{
    private const USER_ID = '22222222-2222-2222-2222-222222222222';
    private const FIGHT_ID = '55555555-5555-4555-8555-555555555555';
    private const FIGHT_JSON = '{"id":{"value":"55555555-5555-4555-8555-555555555555"}}';

    public function test_verified_scores_are_declared_for_the_verified_user(): void
    {
        $dispatched = null;

        $event = new OnUpdateFightResultsVerifiedEvent(self::FIGHT_ID, self::USER_ID, 3, 1);
        new UpdateFightResultsVerifiedEventSubscriber($this->messageBus($dispatched))->updateFightResults($event);

        $this->assertInstanceOf(UpdateFightResultsCommand::class, $dispatched);
        $this->assertSame(self::FIGHT_ID, $dispatched->getFightId());
        $this->assertSame(self::USER_ID, $dispatched->getUser());
        $this->assertSame(3, $dispatched->getScore());
        $this->assertSame(1, $dispatched->getOpponentScore());
        $this->assertSame(self::FIGHT_JSON, $event->getUpdatedFight());
    }

    public function test_a_verified_confirmation_is_made_for_the_verified_user(): void
    {
        $dispatched = null;

        $event = new OnFightResultsConfirmationUserVerified(self::FIGHT_ID, self::USER_ID);
        new FightResultsConfirmationUserVerifiedEventSubscriber($this->messageBus($dispatched))->confirmFightResults($event);

        $this->assertInstanceOf(ConfirmFightResultsCommand::class, $dispatched);
        $this->assertSame(self::FIGHT_ID, $dispatched->getFightId());
        $this->assertSame(self::USER_ID, $dispatched->getUser());
        $this->assertSame(self::FIGHT_JSON, $event->getConfirmedFight());
    }

    public function test_a_refusal_from_the_handler_is_not_swallowed(): void
    {
        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(
            static fn (object $command): Envelope => throw new HandlerFailedException(
                new Envelope($command),
                [new PermissionDeniedException('the declaring side cannot confirm its own outcome')],
            )
        );

        $this->expectException(HandlerFailedException::class);

        new FightResultsConfirmationUserVerifiedEventSubscriber($messageBus)
            ->confirmFightResults(new OnFightResultsConfirmationUserVerified(self::FIGHT_ID, self::USER_ID));
    }

    private function messageBus(?object &$dispatched): MessageBusInterface
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())->method('dispatch')->willReturnCallback(
            static function (object $command) use (&$dispatched): Envelope {
                $dispatched = $command;

                return new Envelope($command, [new HandledStamp(self::FIGHT_JSON, 'handler')]);
            }
        );

        return $messageBus;
    }
}
