<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnPlayerCreationAdminVerifiedEvent;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Player\Application\Event\OnPlayerCreationRequestedEvent;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PlayerCreationRequestedEventSubscriber implements EventSubscriberInterface
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
            OnPlayerCreationRequestedEvent::class => 'validateUser',
        ];
    }

    public function validateUser(OnPlayerCreationRequestedEvent $event): void
    {
        $user = $this->currentUserProvider->getUser();

        if (!$this->currentUserProvider->isGranted('ROLE_USER')) {
            throw new PermissionDeniedException('the user does not have the necessary permissions');
        }

        $this->eventDispatcher->dispatch(new OnPlayerCreationAdminVerifiedEvent(
            $event->getBattletag(),
            (string) $user->getId(),
            $event->getGame(),
        ));
    }
}
