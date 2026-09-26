<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnUpdateFightResultsVerifiedEvent;
use App\Competition\Fight\Application\Model\UpdateFightResultsCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class UpdateFightResultsVerifiedEventSubscriber implements EventSubscriberInterface
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnUpdateFightResultsVerifiedEvent::class => 'updateFightResults',
        ];
    }

    public function updateFightResults(OnUpdateFightResultsVerifiedEvent $event): void
    {
        $event->setUpdatedFight($this->handle(new UpdateFightResultsCommand(
            $event->getFight(),
            $event->getUser(),
            $event->getScore(),
            $event->getOpponentScore(),
        )));
    }
}
