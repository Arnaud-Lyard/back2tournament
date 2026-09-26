<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\EventSubscriber;

use App\Competition\Competitor\Application\Event\OnCompetitorsReadyEvent;
use App\Competition\Fight\Application\Model\CreateFightCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class OnCompetitorsReadyEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnCompetitorsReadyEvent::class => 'createFight',
        ];
    }

    public function createFight(OnCompetitorsReadyEvent $event): void
    {
        $createFightCommand = new CreateFightCommand();
        $createFightCommand->setCompetitorOne($event->getCompetitorOne());
        $createFightCommand->setCompetitorTwo($event->getCompetitorTwo());

        $this->messageBus->dispatch($createFightCommand);
    }
}
