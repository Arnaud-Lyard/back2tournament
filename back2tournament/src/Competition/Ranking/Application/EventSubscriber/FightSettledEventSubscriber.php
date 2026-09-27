<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\EventSubscriber;

use App\Competition\Fight\Domain\Event\FightSettledEvent;
use App\Competition\Ranking\Application\Model\RateFightCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * A fight both sides agree on moves their ratings.
 */
final class FightSettledEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FightSettledEvent::class => 'rateFight',
        ];
    }

    public function rateFight(FightSettledEvent $event): void
    {
        $this->messageBus->dispatch(new RateFightCommand($event->getFightId()->getValue()));
    }
}
