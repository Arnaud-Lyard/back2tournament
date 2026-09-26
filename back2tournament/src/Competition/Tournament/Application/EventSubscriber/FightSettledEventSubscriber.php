<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\EventSubscriber;

use App\Competition\Fight\Domain\Event\FightSettledEvent;
use App\Competition\Tournament\Application\Model\AdvanceTournamentCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

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
            FightSettledEvent::class => 'advanceTournament',
        ];
    }

    public function advanceTournament(FightSettledEvent $event): void
    {
        if (null === $event->getTournamentId()) {
            return;
        }

        $this->messageBus->dispatch(new AdvanceTournamentCommand(
            $event->getFightId()->getValue(),
            $event->getWinner()?->getValue(),
        ));
    }
}
