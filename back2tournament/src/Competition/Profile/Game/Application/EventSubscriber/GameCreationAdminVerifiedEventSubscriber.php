<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnGameCreationAdminVerifiedEvent;
use App\Competition\Profile\Game\Application\Model\CreateGameCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class GameCreationAdminVerifiedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnGameCreationAdminVerifiedEvent::class => 'createGame',
        ];
    }

    public function createGame(OnGameCreationAdminVerifiedEvent $event): void
    {
        $createGameCommand = new CreateGameCommand();
        $createGameCommand->setTitle($event->getTitle());
        $createGameCommand->setUser($event->getUser());

        $this->messageBus->dispatch($createGameCommand);
    }
}
