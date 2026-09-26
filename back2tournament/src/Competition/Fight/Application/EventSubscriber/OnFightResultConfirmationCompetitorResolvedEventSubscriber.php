<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\EventSubscriber;

use App\Competition\Competitor\Application\Event\OnFightResultConfirmationCompetitorResolvedEvent;
use App\Competition\Fight\Application\Model\ConfirmFightResultsCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class OnFightResultConfirmationCompetitorResolvedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnFightResultConfirmationCompetitorResolvedEvent::class => 'onFightCompetitorResolved',
        ];
    }

    public function onFightCompetitorResolved(OnFightResultConfirmationCompetitorResolvedEvent $event): void
    {
        $confirmFightResultsCommand = new ConfirmFightResultsCommand();
        $confirmFightResultsCommand->setFight($event->getFight());
        $confirmFightResultsCommand->setGame($event->getGame());
        $confirmFightResultsCommand->setCompetitorOne($event->getCompetitorOne());
        $confirmFightResultsCommand->setCompetitorTwo($event->getCompetitorTwo());

        $this->messageBus->dispatch($confirmFightResultsCommand);
    }
}
