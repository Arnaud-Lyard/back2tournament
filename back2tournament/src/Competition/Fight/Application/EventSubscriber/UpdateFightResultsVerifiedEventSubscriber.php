<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\EventSubscriber;

use App\Competition\Competitor\Application\Event\OnFightCompetitorResolvedEvent;
use App\Competition\Fight\Application\Model\UpdateFightResultsCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class UpdateFightResultsVerifiedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnFightCompetitorResolvedEvent::class => 'onFightCompetitorResolved',
        ];
    }

    public function onFightCompetitorResolved(OnFightCompetitorResolvedEvent $event): void
    {
        $updateFightResultsCommand = new UpdateFightResultsCommand();
        $updateFightResultsCommand->setFight($event->getFight());
        $updateFightResultsCommand->setUser($event->getUser());
        $updateFightResultsCommand->setCompetitorOne($event->getCompetitorOne());
        $updateFightResultsCommand->setCompetitorTwo($event->getCompetitorTwo());
        $updateFightResultsCommand->setCompetitorOneStatus($event->getCompetitorOneStatus());
        $updateFightResultsCommand->setCompetitorOneScore($event->getCompetitorOneScore());
        $updateFightResultsCommand->setCompetitorTwoStatus($event->getCompetitorTwoStatus());
        $updateFightResultsCommand->setCompetitorTwoScore($event->getCompetitorTwoScore());

        $this->messageBus->dispatch($updateFightResultsCommand);
    }
}
