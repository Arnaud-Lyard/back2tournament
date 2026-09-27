<?php

declare(strict_types=1);

namespace App\Tests\Competition\Ranking\Application;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Event\FightSettledEvent;
use App\Competition\Ranking\Application\EventSubscriber\FightSettledEventSubscriber;
use App\Competition\Ranking\Application\Model\RateFightCommand;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class FightSettledEventSubscriberTest extends TestCase
{
    private const FIGHT_ID = '11111111-1111-4111-8111-111111111111';
    private const WINNER_ID = '22222222-2222-4222-8222-222222222222';

    public function test_a_settled_fight_is_sent_to_the_rankings(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (object $message): bool => $message instanceof RateFightCommand && self::FIGHT_ID === $message->getFightId()))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        new FightSettledEventSubscriber($messageBus)->rateFight(new FightSettledEvent(new FightId(self::FIGHT_ID), new CompetitorId(self::WINNER_ID), null));
    }

    public function test_it_listens_to_every_settled_fight(): void
    {
        $this->assertSame([FightSettledEvent::class => 'rateFight'], FightSettledEventSubscriber::getSubscribedEvents());
    }
}
