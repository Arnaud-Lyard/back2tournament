<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnTeamCreationUserVerifiedEvent;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Team\Application\Event\OnTeamCreationRequestedEvent;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TeamCreationRequestedEventSubscriber implements EventSubscriberInterface
{
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnTeamCreationRequestedEvent::class => 'validateUser',
        ];
    }

    public function validateUser(OnTeamCreationRequestedEvent $event): void
    {
        $user = $this->currentUserProvider->getUser();

        if (!$this->currentUserProvider->isGranted('ROLE_USER')) {
            throw new PermissionDeniedException('the user does not have the necessary permissions');
        }

        $this->eventDispatcher->dispatch(new OnTeamCreationUserVerifiedEvent(
            $event->getName(),
            (string) $user->getId(),
            $event->getPlayer(),
            $event->getLeader(),
        ));
    }
}
