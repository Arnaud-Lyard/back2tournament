<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnTeamCreationUserVerifiedEvent;
use App\Competition\Profile\Team\Application\Model\CreateTeamCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class TeamCreationUserVerifiedEventSubscriber implements EventSubscriberInterface
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnTeamCreationUserVerifiedEvent::class => 'createTeam',
        ];
    }

    public function createTeam(OnTeamCreationUserVerifiedEvent $event): void
    {
        $event->setCreatedTeam($this->handle(new CreateTeamCommand(
            $event->getClan(),
            $event->getName(),
            $event->getSize(),
            $event->getPlayers(),
            $event->getLeader(),
            $event->getUser(),
        )));
    }
}
