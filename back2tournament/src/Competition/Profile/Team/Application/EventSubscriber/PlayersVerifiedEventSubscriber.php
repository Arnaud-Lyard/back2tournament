<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\EventSubscriber;

use App\Competition\Profile\Player\Application\Event\OnPlayersVerifiedEvent;
use App\Competition\Profile\Team\Application\Model\CreateTeamCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class PlayersVerifiedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnPlayersVerifiedEvent::class => 'createTeam',
        ];
    }

    public function createTeam(OnPlayersVerifiedEvent $event): void
    {
        $createTeamCommand = new CreateTeamCommand();
        $createTeamCommand->setName($event->getName());
        $createTeamCommand->setPlayer($event->getPlayer());
        $createTeamCommand->setLeader($event->getLeader());

        $this->messageBus->dispatch($createTeamCommand);
    }
}
